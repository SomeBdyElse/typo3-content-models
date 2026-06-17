<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

final readonly class Relation implements HandlerInterface
{
    public function supports(FieldTypeInterface $field): bool
    {
        return $field instanceof RelationalFieldTypeInterface;
    }

    public function generate(
        string $table,
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $field,
    ): GeneratedField {
        $fieldName = $field->getName();

        return new GeneratedField(
            name: $fieldName,
            nativeType: LazyRecordCollection::class,
            fromRecordExpression: "\$record->get('{$fieldName}') ?? new \\" . LazyRecordCollection::class . '(null, fn() => [])',
            uses: [LazyRecordCollection::class],
        );
    }
}
