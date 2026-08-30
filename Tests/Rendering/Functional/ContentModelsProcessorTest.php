<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Tests\Rendering\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ContentModelsProcessorTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
    ];

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/content_models',
        'typo3conf/ext/content_models/Tests/Rendering/Functional/Fixtures/Extensions/content_models_processor_test',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/content-models-processor.csv');
        $this->writeSiteConfiguration();
        $this->setUpFrontendRootPage(1, [
            'setup' => [
                'EXT:content_models/Tests/Rendering/Functional/Fixtures/TypoScript/content-models-processor.typoscript',
            ],
        ]);
    }

    #[Test]
    public function convertsPageContentProcessorRecordsToContentModels(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(2));
        $body = (string)$response->getBody();

        self::assertSame(200, $response->getStatusCode(), $body);
        self::assertStringContainsString(
            'processor-probe-content-model:10|Processor Probe Content',
            $body,
        );
    }

    private function writeSiteConfiguration(): void
    {
        $siteConfigurationPath = Environment::getConfigPath() . '/sites/processor-test';
        GeneralUtility::mkdir_deep($siteConfigurationPath);
        GeneralUtility::writeFile($siteConfigurationPath . '/config.yaml', <<<'YAML'
rootPageId: 1
base: 'http://localhost/'
languages:
  -
    title: English
    enabled: true
    languageId: 0
    base: /
    locale: en_US.UTF-8
    navigationTitle: English
    flag: us
    hreflang: en-US
    direction: ltr
YAML);
    }
}
