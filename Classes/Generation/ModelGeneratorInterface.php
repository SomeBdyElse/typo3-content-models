<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

interface ModelGeneratorInterface
{
    public function generateModel(
        string $table,
        ?string $type,
    ): mixed;
}
