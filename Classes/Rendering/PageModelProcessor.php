<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModelFactoryInterface;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Page\PageInformation;

class PageModelProcessor implements DataProcessorInterface
{
    public function __construct(
        protected RecordFactory $recordFactory,
        protected ContentModelFactoryInterface $contentModelFactory,
    ) {
    }

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        $sourceVariableName = $cObj->stdWrapValue('source', $processorConfiguration, 'page');
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'pageModel');

        $source = $processedData[$sourceVariableName] ?? null;
        if ($source instanceof PageInformation) {
            $source = $this->recordFactory->createResolvedRecordFromDatabaseRow(
                'pages',
                $source->getPageRecord(),
            );
        }

        if (!$source instanceof Record) {
            return $processedData;
        }

        $pageRecord = $source;
        $processedData[$targetVariableName] = ($this->contentModelFactory)($pageRecord);

        return $processedData;
    }
}
