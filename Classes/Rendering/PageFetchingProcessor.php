<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Page\PageInformation;

class PageFetchingProcessor implements DataProcessorInterface
{
    public function __construct(
        protected RecordFactory $recordFactory,
    ) {
    }

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        /** @var PageInformation $pageInformation */
        $pageInformation = $processedData['page'];
        $pageRecord = $this->recordFactory->createResolvedRecordFromDatabaseRow(
            'pages',
            $pageInformation->getPageRecord(),
        );

        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'page-record');
        $processedData[$targetVariableName] = $pageRecord;

        return $processedData;
    }
}
