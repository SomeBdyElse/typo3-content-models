<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\AsFieldGenerationHandler;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

#[AsFieldGenerationHandler(
    identifier: 'somebdyelse/typo3-content-models/date-time',
)]
final readonly class DateTime implements HandlerInterface
{
    public function supports(FieldTypeInterface $field): bool
    {
        return $field instanceof DateTimeFieldType;
    }

    public function generate(
        string $table,
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $field,
    ): GeneratedField {
        return new GeneratedField(
            name: $field->getName(),
            nativeType: '?' . \DateTimeImmutable::class,
            fromRecordExpression: "\$record->get('{$field->getName()}')",
            uses: [\DateTimeImmutable::class],
        );
    }
}
