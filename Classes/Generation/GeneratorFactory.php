<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

use SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GeneratorFactory
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    public function getContentModelGenerator(string $table, ?string $type): ModelGenerator
    {
        $configuration = $this->configuration->getTableConfiguration($table, $type);
        $generatorClass = $configuration['generator'] ?? DefaultGenerator::class;
        $generatorClass = ltrim($generatorClass, '\\');

        if (!is_a($generatorClass, ModelGenerator::class, true)) {
            throw new \RuntimeException(sprintf(
                'The configured generator for table "%s"%s must be a PHP class and implement %s.',
                $table,
                $type === null ? '' : sprintf(' and type "%s"', $type),
                ModelGenerator::class,
            ), 1781337824);
        }

        return GeneralUtility::makeInstance($generatorClass);
    }

    public function getCommonCodeGenerator(): CommonCodeGenerator
    {
        $generatorClass = $this->configuration->settings['commonCodeGenerator'] ?? DefaultGenerator::class;
        $generatorClass = ltrim($generatorClass, '\\');

        if (!is_a($generatorClass, CommonCodeGenerator::class, true)) {
            throw new \RuntimeException(sprintf(
                'The configured common code generator must be a PHP class and implement %s.',
                CommonCodeGenerator::class,
            ), 1781337826);
        }

        return GeneralUtility::makeInstance($generatorClass);
    }
}
