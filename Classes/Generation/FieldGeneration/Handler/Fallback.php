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
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

final readonly class Fallback implements HandlerInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
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
        return new GeneratedField(
            name: $field->getName(),
            nativeType: $this->getDatabasePhpType($table, $field),
            fromRecordExpression: "\$record->get('{$field->getName()}')",
        );
    }

    private function getDatabasePhpType(string $table, FieldTypeInterface $field): string
    {
        $connection = $this->connectionPool->getConnectionForTable($table);
        $schemaInfo = $connection->getSchemaInformation();
        $column = $schemaInfo->getTableInfo($table)->getColumnInfo($field->getName());
        if ($column === null) {
            return Type::Mixed;
        }

        $baseType = match (get_class($column->getType())) {
            StringType::class => Type::String,
            IntegerType::class => Type::Int,
            BooleanType::class => Type::Bool,
            DateTimeType::class => \DateTimeInterface::class,
            SmallIntType::class => match ($field->getType()) {
                'check' => Type::Bool,
                'radio' => Type::Int,
                default => Type::Int,
            },
            BigIntType::class => match ($field->getType()) {
                'datetime' => \DateTimeInterface::class,
                default => Type::Int,
            },
            TextType::class => Type::String,
            default => Type::Mixed,
        };

        return $baseType === Type::Mixed ? $baseType : Type::nullable($baseType, !$column->notNull);
    }
}
