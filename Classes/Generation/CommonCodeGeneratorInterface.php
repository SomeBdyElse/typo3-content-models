<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

interface CommonCodeGeneratorInterface
{
    public function generateCommonCode(array $generatedModels = []): void;
}
