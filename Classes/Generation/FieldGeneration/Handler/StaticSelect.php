<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\AsFieldGenerationHandler;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\StaticSelectFieldType;
use TYPO3\CMS\Core\Schema\TcaSchema;

#[AsFieldGenerationHandler(
    identifier: 'somebdyelse/typo3-content-models/static-select',
)]
final readonly class StaticSelect implements HandlerInterface
{
    public function supports(FieldTypeInterface $field): bool
    {
        if (!$field instanceof StaticSelectFieldType) {
            return false;
        }

        return (string)($field->getConfiguration()['renderType'] ?? '') !== 'selectSingle';
    }

    public function generate(
        string $table,
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $field,
    ): GeneratedField {
        return new GeneratedField(
            name: $field->getName(),
            nativeType: 'array',
            fromRecordExpression: "\$record->get('{$field->getName()}')",
        );
    }
}
