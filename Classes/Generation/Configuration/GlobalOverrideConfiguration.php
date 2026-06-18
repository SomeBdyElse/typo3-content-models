<?php

namespace SomeBdyElse\Typo3ContentModels\Generation\Configuration;

readonly class GlobalOverrideConfiguration extends OverrideConfiguration
{
    /**
     * @param array<string, TableOverrideConfiguration> $tables
     * @param array<string, FieldOverrideConfiguration>|null $fields
     */
    public function __construct(
        ?bool $generate = null,
        ?string $className = null,
        ?array $fields = null,
        public array $tables = [],
    ) {
        parent::__construct($generate, $className, $fields);
    }
}
