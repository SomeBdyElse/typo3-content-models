<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

use SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration;
use SomeBdyElse\Typo3ContentModels\Generation\Exception\NoGeneratorConfiguredException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GeneratorFactory
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    public function getContentModelGenerator(string $table, ?string $type): ModelGeneratorInterface
    {
        $configuration = $this->configuration->getTableConfiguration($table, $type);
        $generatorClass = $configuration['generator'] ?? throw new NoGeneratorConfiguredException($table, $type);
        $generatorClass = ltrim($generatorClass, '\\');

        if (!is_a($generatorClass, ModelGeneratorInterface::class, true)) {
            throw new \RuntimeException(sprintf(
                'The configured generator for table "%s"%s must be a PHP class and implement %s.',
                $table,
                $type === null ? '' : sprintf(' and type "%s"', $type),
                ModelGeneratorInterface::class,
            ), 1781337824);
        }

        return GeneralUtility::makeInstance($generatorClass);
    }

    public function getCommonCodeGenerator(): ?CommonCodeGeneratorInterface
    {
        $generatorClass = $this->configuration->settings['commonCodeGenerator'] ?? null;
        if (!isset($generatorClass)) {
            return null;
        }
        $generatorClass = ltrim($generatorClass, '\\');

        if (!is_a($generatorClass, CommonCodeGeneratorInterface::class, true)) {
            throw new \RuntimeException(sprintf(
                'The configured common code generator must be a PHP class and implement %s.',
                CommonCodeGeneratorInterface::class,
            ), 1781337826);
        }

        return GeneralUtility::makeInstance($generatorClass);
    }
}
