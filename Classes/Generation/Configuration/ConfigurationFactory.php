<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\Configuration;

use SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration;
use SomeBdyElse\Typo3ContentModels\Generation\GeneratorFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final readonly class ConfigurationFactory
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private GeneratorFactory $generatorFactory,
    ) {
    }

    public function create(): Configuration
    {
        $settings = $this->extensionConfiguration->get('content_models');

        return new Configuration(
            targetPhpNamespace: $this->getStringSetting(
                $settings,
                'targetPhpNamespace',
                'Typo3Api\\Typo3Api\\Model\\Content',
            ),
            targetDirectory: $this->getStringSetting(
                $settings,
                'targetDirectory',
                'EXT:typo3_api/Classes/Model/Content',
            ),
            generateComposerJson: $this->getBoolSetting(
                $settings,
                'generateComposerJson',
                false,
            ),
            overrides: $this->getOverrideSetting(
                $settings,
                'overrides',
                new GlobalOverrideConfiguration(),
            )
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function getStringSetting(array $settings, string $key, string $default): string
    {
        $value = $settings[$key] ?? $default;
        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function getBoolSetting(array $settings, string $key, bool $default): bool
    {
        $value = $settings[$key] ?? $default;
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function getOverrideSetting(array $settings, string $key, GlobalOverrideConfiguration $default): GlobalOverrideConfiguration
    {
        $value = $settings[$key] ?? $default;
        if (!$value instanceof GlobalOverrideConfiguration) {
            throw new \RuntimeException("$key must be a " . GlobalOverrideConfiguration::class);
        }
        return $value;
    }
}
