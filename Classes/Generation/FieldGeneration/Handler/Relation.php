<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration;
use SomeBdyElse\Typo3ContentModels\Generation\Configuration\FieldOverrideConfiguration;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use SomeBdyElse\Typo3ContentModels\Generation\NamingHelper;
use SomeBdyElse\Typo3ContentModels\Rendering\LazyContentModelCollection;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

final readonly class Relation implements HandlerInterface
{
    public function __construct(
        private NamingHelper $namingHelper,
        private TcaSchemaFactory $schemaFactory,
        private Configuration $configuration,
    ) {
    }

    public function supports(FieldTypeInterface $field): bool
    {
        return $field instanceof RelationalFieldTypeInterface
            && $this->resolveTargetTables($field) !== [];
    }

    public function generate(
        string $table,
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $field,
    ): GeneratedField {
        $fieldName = $field->getName();
        $targetItemClassNames = $this->resolveItemClassNames($table, $type, $field);
        $targetItemClassReference = $this->classReference($targetItemClassNames);
        $recordCollectionFallback = 'new \\' . LazyRecordCollection::class . '(null, fn() => [])';

        return new GeneratedField(
            name: $fieldName,
            nativeType: LazyContentModelCollection::class,
            fromRecordExpression: "\\SomeBdyElse\\Typo3ContentModels\\Rendering\\LazyContentModelCollection::fromRecords(\$record->get('{$fieldName}') ?? {$recordCollectionFallback}, {$targetItemClassReference})",
            uses: [LazyContentModelCollection::class],
            phpDocType: '\\' . LazyContentModelCollection::class . '<' . $this->templateArgument($targetItemClassNames) . '>',
        );
    }

    /**
     * @param non-empty-list<class-string> $classNames
     */
    private function classReference(array $classNames): string
    {
        if (count($classNames) === 1) {
            return '\\' . ltrim($classNames[0], '\\') . '::class';
        }

        $references = [];
        foreach ($classNames as $className) {
            $references[] = '\\' . ltrim($className, '\\') . '::class';
        }

        return '[' . implode(', ', $references) . ']';
    }

    /**
     * @param non-empty-list<class-string> $classNames
     */
    private function templateArgument(array $classNames): string
    {
        return implode('|', array_map(
            static fn (string $className): string => '\\' . ltrim($className, '\\'),
            $classNames,
        ));
    }

    /**
     * @return non-empty-list<class-string<ContentModelInterface>|class-string<Record>>
     */
    private function resolveItemClassNames(string $table, ?string $type, FieldTypeInterface $field): array
    {
        $targetTables = $this->resolveTargetTables($field);
        if ($targetTables === []) {
            return [Record::class];
        }

        $fieldOverride = ($this->configuration->overrides[$table][$type] ?? null)?->fields[$field->getName()] ?? null;
        $itemClassNames = [];
        foreach ($targetTables as $targetTable) {
            try {
                $targetSchema = $this->schemaFactory->get($targetTable);
            } catch (\Throwable) {
                $itemClassNames[] = Record::class;
                continue;
            }

            $targetModelClassNames = [];
            foreach ($targetSchema->getSubSchemata() as $targetType => $targetSubSchema) {
                if (!$this->allowsTargetType($fieldOverride, $targetTable, (string)$targetType)) {
                    continue;
                }

                $generate = ($this->configuration->overrides[$targetTable][$targetType] ?? null)?->generate ?? true;
                if (!$generate) {
                    continue;
                }

                $className = ($this->configuration->overrides[$targetTable][$targetType] ?? null)?->className
                    ?? $this->namingHelper->classNameForType($targetTable, (string)$targetType);

                $targetModelClassNames[] = $this->namingHelper->namespaceForTable(
                    $this->configuration->targetPhpNamespace,
                    $targetTable,
                ) . '\\' . $className;
            }

            if ($targetModelClassNames === []) {
                $itemClassNames[] = Record::class;
                continue;
            }

            array_push($itemClassNames, ...$targetModelClassNames);
        }

        return array_values(array_unique($itemClassNames));
    }

    private function allowsTargetType(?FieldOverrideConfiguration $fieldOverride, string $targetTable, string $targetType): bool
    {
        if ($fieldOverride === null || $fieldOverride->relationTargetTypes === []) {
            return true;
        }

        if (!array_key_exists($targetTable, $fieldOverride->relationTargetTypes)) {
            return true;
        }

        return in_array($targetType, array_map('strval', $fieldOverride->relationTargetTypes[$targetTable]), true);
    }

    /**
     * @return list<string>
     */
    private function resolveTargetTables(FieldTypeInterface $field): array
    {
        $configuration = $field->getConfiguration();
        if (isset($configuration['foreign_table']) && is_string($configuration['foreign_table'])) {
            return $this->normalizeTargetTables($configuration['foreign_table']);
        }
        if (isset($configuration['allowed']) && is_string($configuration['allowed'])) {
            return $this->normalizeTargetTables($configuration['allowed']);
        }
        if ($field instanceof RelationalFieldTypeInterface) {
            $tables = [];
            foreach ($field->getRelations() as $relation) {
                $tables[] = $relation->toTable();
            }
            return $this->normalizeTargetTables(...$tables);
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function normalizeTargetTables(string ...$targetTables): array
    {
        $normalized = [];
        foreach ($targetTables as $targetTableList) {
            foreach (explode(',', $targetTableList) as $targetTable) {
                $targetTable = trim($targetTable);
                if ($targetTable === '' || $targetTable === '*') {
                    continue;
                }
                $normalized[$targetTable] = $targetTable;
            }
        }

        return array_values($normalized);
    }
}
