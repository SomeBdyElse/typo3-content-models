<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

interface CommonCodeGenerator
{
    public function generateCommonCode(array $generatedModels = []): void;
}
