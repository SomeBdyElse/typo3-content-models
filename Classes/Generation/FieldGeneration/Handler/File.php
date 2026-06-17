<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\FileFieldType;
use TYPO3\CMS\Core\Schema\TcaSchema;

final readonly class File implements HandlerInterface
{
    public function supports(FieldTypeInterface $field): bool
    {
        return $field instanceof FileFieldType;
    }

    public function generate(
        string $table,
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $field,
    ): GeneratedField {
        return new GeneratedField(
            name: $field->getName(),
            nativeType: LazyFileReferenceCollection::class,
            fromRecordExpression: "\$record->get('{$field->getName()}')",
            uses: [LazyFileReferenceCollection::class],
        );
    }
}
