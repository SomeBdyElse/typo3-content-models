<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class GeneratorFactory
{
    public function getContentModelGenerator(string $table, string $type): ModelGenerator
    {
        return GeneralUtility::makeInstance(DefaultGenerator::class);
    }

    public function getCommonCodeGenerator(): CommonCodeGenerator
    {
        return GeneralUtility::makeInstance(DefaultGenerator::class);
    }
}