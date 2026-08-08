<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\Configuration;

use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ArrayUtility;

final readonly class ConfigurationFactory
{
    private const DEFAULT_CONFIGURATION_FILE = 'EXT:content_models/Resources/Private/Configuration/default_configuration.yaml';
    private const PROJECT_CONFIGURATION_FILE = 'system/content_models.yaml';

    public function __construct(
        private YamlFileLoader $yamlFileLoader,
    ) {
    }

    public function create(): Configuration
    {
        $settings = $this->loadSettings();

        return new Configuration($settings);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadSettings(): array
    {
        $settings = $this->yamlFileLoader->load(self::DEFAULT_CONFIGURATION_FILE);
        $projectConfigurationFile = Environment::getConfigPath() . '/' . self::PROJECT_CONFIGURATION_FILE;

        if (is_file($projectConfigurationFile)) {
            $projectSettings = $this->yamlFileLoader->load(
                $projectConfigurationFile,
                YamlFileLoader::PROCESS_PLACEHOLDERS
                | YamlFileLoader::PROCESS_IMPORTS
                | YamlFileLoader::ALLOW_EMPTY_FILE,
            );
            ArrayUtility::mergeRecursiveWithOverrule($settings, $projectSettings);
        }

        unset($settings['$schema']);

        return $settings;
    }
}
