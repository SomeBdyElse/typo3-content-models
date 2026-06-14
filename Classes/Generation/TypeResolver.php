<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Nette\PhpGenerator\Type;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Domain\RecordPropertyClosure;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\FileFieldType;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\StaticSelectFieldType;
use TYPO3\CMS\Core\Schema\TcaSchema;

class TypeResolver
{
    public function __construct(
        protected ConnectionPool $connectionPool,
    ) {
    }

    public function getTypeForField(
        string $tableName, 
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $fieldInformation
    ): string {
        $fieldType = $fieldInformation->getType();

        if ($fieldInformation instanceof FileFieldType) {
            return LazyFileReferenceCollection::class;
        }

        if ($fieldInformation instanceof RelationalFieldTypeInterface) {
            return LazyRecordCollection::class;
        }

        if (
            $fieldInformation->isType(TableColumnType::FOLDER)
        ) {
            return RecordPropertyClosure::class;
        }

        if ($fieldInformation instanceof StaticSelectFieldType) {
            $selectForcedToSingle = (string)($fieldInformation->getConfiguration()['renderType'] ?? '') === 'selectSingle';
            if (!$selectForcedToSingle) {
                return Type::Array;
            }
        }

        if (
            $fieldInformation->isType(TableColumnType::FLEX)
            || $fieldInformation->isType(TableColumnType::JSON)
        ) {
            return RecordPropertyClosure::class;
        }

        if ($fieldInformation instanceof DateTimeFieldType) {
            return '?' . \DateTimeImmutable::class;
        }

        if ($fieldInformation->isType(TableColumnType::LINK)) {
            return TypolinkParameter::class;
        }
        
        return $this->getDatabasePhpType($tableName, $fieldInformation, $fieldType);
    }

    protected function getDatabasePhpType(string $tableName, FieldTypeInterface $field, string $fieldType): string
    {
        $connection = $this->connectionPool->getConnectionForTable($tableName);
        $schemaInfo = $connection->getSchemaInformation();
        $column = $schemaInfo->getTableInfo($tableName)->getColumnInfo($field->getName());
        if ($column === null) {
            return Type::Mixed;
        }
        $typeClassName = get_class($column->getType());

        $baseType = match ($typeClassName) {
            StringType::class => Type::String,
            IntegerType::class => Type::Int,
            BooleanType::class => Type::Bool,
            DateTimeType::class => \DateTimeInterface::class,
            SmallIntType::class => match ($fieldType) {
                'check' => Type::Bool,
                'radio' => Type::Int,
            },
            BigIntType::class => match ($fieldType) {
                'datetime' => \DateTimeInterface::class,
                default => Type::Int,
            },
            TextType::class => Type::String,
            default => Type::Mixed,
        };
        
        $result = $baseType === Type::Mixed ? $baseType : Type::nullable($baseType, !$column->notNull);
        return $result;
    }
}
