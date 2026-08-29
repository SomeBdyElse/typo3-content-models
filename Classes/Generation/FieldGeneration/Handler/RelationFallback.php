<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\AsFieldGenerationHandler;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use SomeBdyElse\Typo3ContentModels\Rendering\LazyContentModelCollection;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

#[AsFieldGenerationHandler(
    identifier: 'somebdyelse/typo3-content-models/relation-fallback',
    after: [
        'somebdyelse/typo3-content-models/file',
        'somebdyelse/typo3-content-models/relation',
    ],
)]
final readonly class RelationFallback implements HandlerInterface
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
        $recordCollectionFallback = 'new \\' . LazyRecordCollection::class . '(null, fn() => [])';

        return new GeneratedField(
            name: $fieldName,
            nativeType: LazyContentModelCollection::class,
            fromRecordExpression: "\\SomeBdyElse\\Typo3ContentModels\\Rendering\\LazyContentModelCollection::fromRecords(\$record->get('{$fieldName}') ?? {$recordCollectionFallback}, \\" . Record::class . '::class)',
            uses: [LazyContentModelCollection::class],
            phpDocType: '\\' . LazyContentModelCollection::class . '<\\' . Record::class . '>',
        );
    }
}
