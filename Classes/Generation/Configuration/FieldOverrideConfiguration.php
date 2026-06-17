<?php

namespace SomeBdyElse\Typo3ContentModels\Generation\Configuration;

readonly class FieldOverrideConfiguration
{
    /**
     * @param array<string, list<int|string>> $relationTargetTypes
     */
    public function __construct(
        public array $relationTargetTypes = [],
    ) {
    }
}
