<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

interface ModelGenerator
{
    public function generateModel(string $table, ?string $type): mixed;
}
