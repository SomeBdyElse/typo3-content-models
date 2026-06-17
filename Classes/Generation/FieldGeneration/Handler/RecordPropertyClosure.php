<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Domain\RecordPropertyClosure as Typo3RecordPropertyClosure;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

final readonly class RecordPropertyClosure implements HandlerInterface
{
    public function supports(FieldTypeInterface $field): bool
    {
        return $field->isType(TableColumnType::FOLDER)
            || $field->isType(TableColumnType::FLEX)
            || $field->isType(TableColumnType::JSON);
    }

    public function generate(
        string $table,
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $field,
    ): GeneratedField {
        return new GeneratedField(
            name: $field->getName(),
            nativeType: Typo3RecordPropertyClosure::class,
            fromRecordExpression: "\$record->get('{$field->getName()}')",
            uses: [Typo3RecordPropertyClosure::class],
        );
    }
}
