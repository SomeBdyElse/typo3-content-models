<?php

namespace SomeBdyElse\Typo3ContentModels\Generation\Configuration;

readonly class OverrideConfiguration
{
    /**
     * @param array<string, FieldOverrideConfiguration> $fields
     */
    public function __construct(
        public ?bool $generate = null,
        public ?string $className = null,
        public ?array $fields = null,
    ) {
    }
}
