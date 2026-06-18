<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\Configuration;

readonly class Configuration
{
    public string $targetDirectory;
    
    public function __construct(
        public string $targetPhpNamespace,
        string $targetDirectory,
        public bool $generateComposerJson,
        public GlobalOverrideConfiguration $overrides,
    ) {
        $this->targetDirectory = rtrim($targetDirectory, '/');
    }

    public function getTableOverride(string $table, ?string $type = null): OverrideConfiguration
    {
        $override = $this->overrides;

        $tableConfiguration = $this->overrides->tables[$table] ?? null;
        if (!$tableConfiguration instanceof TableOverrideConfiguration) {
            return $override;
        }

        $override = $this->mergeOverrides($override, $tableConfiguration);

        if ($type === null) {
            return $override;
        }

        $typeConfiguration = $tableConfiguration->types[$type] ?? null;
        if (!$typeConfiguration instanceof OverrideConfiguration) {
            return $override;
        }

        return $this->mergeOverrides($override, $typeConfiguration);
    }

    public function getFieldOverride(string $table, ?string $type, string $field): ?FieldOverrideConfiguration
    {
        return $this->getTableOverride($table, $type)->fields[$field] ?? null;
    }

    private function mergeOverrides(OverrideConfiguration $base, OverrideConfiguration $overlay): OverrideConfiguration
    {
        return new OverrideConfiguration(
            generate: $overlay->generate ?? $base->generate,
            className: $overlay->className ?? $base->className,
            fields: $overlay->fields !== null
                ? array_replace($base->fields ?? [], $overlay->fields)
                : $base->fields,
        );
    }
}
