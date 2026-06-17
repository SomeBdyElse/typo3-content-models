<?php

namespace SomeBdyElse\Typo3ContentModels\Generation\Configuration;

readonly class OverrideConfiguration
{
    /**
     * @param array<string, FieldOverrideConfiguration> $fields
     */
    public function __construct(
        public ?string $className = null,
        public bool $generate = true,
        public array $fields = [],
    ) {
    }
}
