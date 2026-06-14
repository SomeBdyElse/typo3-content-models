<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation;

readonly class Configuration
{
    public string $targetDirectory;
    
    public function __construct(
        public string $targetPhpNamespace,
        string $targetDirectory,
        public bool $generateComposerJson,
    ) {
        $this->targetDirectory = rtrim($targetDirectory, '/');
    }
}
