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
        /** @param $overrides array<string, array<string, OverrideConfiguration> */
        public array $overrides,
    ) {
        $this->targetDirectory = rtrim($targetDirectory, '/');
    }
}
