<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

readonly class OverrideConfiguration
{
    public function __construct(
        public ?string $className = null,
        public bool $generate = true,
    ) {
    }
}
