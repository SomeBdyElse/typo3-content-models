<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\AsFieldGenerationHandler;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

#[AsFieldGenerationHandler(
    identifier: 'somebdyelse/typo3-content-models/link',
)]
final readonly class Link implements HandlerInterface
{
    public function supports(FieldTypeInterface $field): bool
    {
        return $field->isType(TableColumnType::LINK);
    }

    public function generate(
        string $table,
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $field,
    ): GeneratedField {
        return new GeneratedField(
            name: $field->getName(),
            nativeType: TypolinkParameter::class,
            fromRecordExpression: "\$record->get('{$field->getName()}')",
            uses: [TypolinkParameter::class],
        );
    }
}
