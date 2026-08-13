<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\Configuration;

use SomeBdyElse\Typo3ContentModels\Generation\CommonCodeGeneratorInterface;
use SomeBdyElse\Typo3ContentModels\Generation\ModelGeneratorInterface;

/**
 * @phpstan-type FieldConfiguration array{relationTargetTypes?: array<string, list<int|string>>, ...}
 * @phpstan-type ContentModelConfiguration array{generate?: bool|null, className?: string|null, generator?: class-string<ModelGeneratorInterface>|null, fields?: array<string, FieldConfiguration>|null, ...}
 * @phpstan-type TableConfiguration array{generate?: bool|null, className?: string|null, generator?: class-string<ModelGeneratorInterface>|null, fields?: array<string, FieldConfiguration>|null, types?: array<string, ContentModelConfiguration>, ...}
 * @phpstan-type GlobalConfiguration array{generate?: bool|null, className?: string|null, generator?: class-string<ModelGeneratorInterface>|null, fields?: array<string, FieldConfiguration>|null, tables?: array<string, TableConfiguration>, ...}
 * @phpstan-type Settings array{targetPhpNamespace: string, targetDirectory: string, commonCodeGenerator?: class-string<CommonCodeGeneratorInterface>|null, overrides?: GlobalConfiguration, ...}
 */
readonly class Configuration
{
    public string $targetPhpNamespace;
    public string $targetDirectory;
    public array $contentModelConfiguration;

    /**
     * @param Settings $settings
     */
    public function __construct(
        public array $settings,
    ) {
        $this->targetPhpNamespace = $settings['targetPhpNamespace'];
        $this->targetDirectory = rtrim($settings['targetDirectory'], '/');
        $this->contentModelConfiguration = $settings['overrides'] ?? [];
    }

    /**
     * @return ContentModelConfiguration
     */
    public function getTableConfiguration(string $table, ?string $type = null): array
    {
        $configuration = $this->contentModelConfiguration;

        $tableConfiguration = $this->contentModelConfiguration['tables'][$table] ?? null;
        if (!is_array($tableConfiguration)) {
            return $configuration;
        }

        $configuration = $this->mergeConfigurations($configuration, $tableConfiguration);

        if ($type === null) {
            return $configuration;
        }

        $typeConfiguration = $tableConfiguration['types'][(string)$type] ?? null;
        if (!is_array($typeConfiguration)) {
            return $configuration;
        }

        return $this->mergeConfigurations($configuration, $typeConfiguration);
    }

    /**
     * @return FieldConfiguration|null
     */
    public function getFieldConfiguration(string $table, ?string $type, string $field): ?array
    {
        return $this->getTableConfiguration($table, $type)['fields'][$field] ?? null;
    }

    /**
     * @param ContentModelConfiguration $base
     * @param ContentModelConfiguration $overlay
     * @return ContentModelConfiguration
     */
    private function mergeConfigurations(array $base, array $overlay): array
    {
        $merged = array_replace($base, $overlay);

        if (array_key_exists('fields', $overlay) && is_array($overlay['fields'])) {
            $merged['fields'] = array_replace($base['fields'] ?? [], $overlay['fields']);
        }

        return $merged;
    }
}
