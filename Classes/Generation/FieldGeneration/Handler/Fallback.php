<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Nette\PhpGenerator\Type;
use SomeBdyElse\Typo3ContentModels\Generation\DatabaseSchema\DatabaseSchemaProviderInterface;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

final readonly class Fallback implements HandlerInterface
{
    public function __construct(
        private DatabaseSchemaProviderInterface $databaseSchemaProvider,
    ) {
    }

    public function supports(FieldTypeInterface $field): bool
    {
        return true;
    }

    public function generate(
        string $table,
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $field,
    ): GeneratedField {
        $nativeType = $this->getDatabasePhpType($table, $field);

        return new GeneratedField(
            name: $field->getName(),
            nativeType: $nativeType,
            fromRecordExpression: "\$record->get('{$field->getName()}')",
            uses: $this->usesForNativeType($nativeType),
        );
    }

    private function getDatabasePhpType(string $table, FieldTypeInterface $field): string
    {
        $column = $this->databaseSchemaProvider->getColumn($table, $field->getName());
        if ($column === null) {
            return Type::Mixed;
        }

        $columnType = $column->getType();
        $baseType = match (true) {
            $columnType instanceof StringType => Type::String,
            $columnType instanceof IntegerType => Type::Int,
            $columnType instanceof BooleanType => Type::Bool,
            $columnType instanceof DateTimeType => \DateTimeInterface::class,
            $columnType instanceof SmallIntType => match ($field->getType()) {
                'check' => Type::Bool,
                'radio' => Type::Int,
                default => Type::Int,
            },
            $columnType instanceof BigIntType => match ($field->getType()) {
                'datetime' => \DateTimeInterface::class,
                default => Type::Int,
            },
            $columnType instanceof TextType => Type::String,
            default => Type::Mixed,
        };

        return $baseType === Type::Mixed ? $baseType : Type::nullable($baseType, !$column->getNotnull());
    }

    /**
     * @return list<class-string>
     */
    private function usesForNativeType(string $nativeType): array
    {
        $fieldType = \Nette\Utils\Type::fromString($nativeType);
        if (!$fieldType->isClass()) {
            return [];
        }

        /** @var class-string $nativeTypeUse */
        $nativeTypeUse = Type::nullable((string)$fieldType, false);

        return [$nativeTypeUse];
    }
}
