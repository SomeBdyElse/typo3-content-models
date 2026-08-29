<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\AsFieldGenerationHandler;
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

#[AsFieldGenerationHandler(
    identifier: 'somebdyelse/typo3-content-models/relation',
    after: ['somebdyelse/typo3-content-models/file'],
)]
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
            static fn(string $className): string => '\\' . ltrim($className, '\\'),
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

        $fieldConfiguration = $this->configuration->getFieldConfiguration($table, $type, $field->getName());
        $itemClassNames = [];
        foreach ($targetTables as $targetTable) {
            $targets = $this->resolveTargets($targetTable, $fieldConfiguration);
            if ($targets === []) {
                $itemClassNames[] = Record::class;
                continue;
            }

            $targetModelClassNames = [];
            foreach ($targets as ['table' => $targetTableName, 'type' => $targetType]) {
                $targetModelClassName = $this->resolveTargetModelClassName($targetTableName, $targetType);
                if ($targetModelClassName !== null) {
                    $targetModelClassNames[] = $targetModelClassName;
                }
            }

            if ($targetModelClassNames === []) {
                $itemClassNames[] = Record::class;
                continue;
            }

            array_push($itemClassNames, ...$targetModelClassNames);
        }

        return array_values(array_unique($itemClassNames));
    }

    /**
     * @param array{relationTargetTypes?: array<string, list<int|string>>, ...}|null $fieldConfiguration
     * @return list<array{table: string, type: ?string}>
     */
    private function resolveTargets(string $targetTable, ?array $fieldConfiguration): array
    {
        if ($fieldConfiguration !== null && array_key_exists($targetTable, $fieldConfiguration['relationTargetTypes'] ?? [])) {
            return array_map(
                static fn(string|int $type): array => ['table' => $targetTable, 'type' => (string)$type],
                $fieldConfiguration['relationTargetTypes'][$targetTable],
            );
        }

        try {
            $targetSchema = $this->schemaFactory->get($targetTable);
        } catch (\Throwable) {
            return [];
        }

        $targetSubSchemata = $targetSchema->getSubSchemata();
        if (count($targetSubSchemata) === 0) {
            return [['table' => $targetTable, 'type' => null]];
        }

        $targets = [];
        foreach ($targetSubSchemata as $type => $_) {
            $targets[] = ['table' => $targetTable, 'type' => (string)$type];
        }

        return $targets;
    }

    private function resolveTargetModelClassName(
        string $targetTable,
        ?string $targetType = null,
    ): ?string {
        $tableConfiguration = $this->configuration->getTableConfiguration($targetTable, $targetType);
        if (!($tableConfiguration['generate'] ?? true)) {
            return null;
        }

        $className = $tableConfiguration['className'] ?? $this->namingHelper->classNameForType($targetTable, $targetType);

        return $this->namingHelper->namespaceForTable($this->configuration->targetPhpNamespace, $targetTable) . '\\' . $className;
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
