<?php

namespace SomeBdyElse\Typo3ContentModels\Generation\Configuration;

readonly class TableOverrideConfiguration extends OverrideConfiguration
{
    /**
     * @param array<string|int, OverrideConfiguration> $types
     * @param array<string, FieldOverrideConfiguration>|null $fields
     */
    public array $types;

    public function __construct(
        ?bool $generate = null,
        ?string $className = null,
        ?array $fields = null,
        array $types = [],
    ) {
        parent::__construct($generate, $className, $fields);
        $this->types = $this->normalizeTypes($types);
    }

    /**
     * @param array<string|int, OverrideConfiguration> $types
     * @return array<string, OverrideConfiguration>
     */
    private function normalizeTypes(array $types): array
    {
        $normalized = [];
        foreach ($types as $type => $configuration) {
            $normalized[(string)$type] = $configuration;
        }

        return $normalized;
    }
}
