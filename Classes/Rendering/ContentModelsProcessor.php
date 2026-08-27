<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Page\ContentAreaCollection;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

class ContentModelsProcessor implements DataProcessorInterface
{
    public function __construct(
        protected ContentConverter $contentConverter,
    ) {
    }

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        $sourceVariableName = $cObj->stdWrapValue('source', $processorConfiguration, 'content');
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, $sourceVariableName);

        $contentAreas = $processedData[$sourceVariableName] ?? null;
        if (!$contentAreas instanceof ContentAreaCollection) {
            return $processedData;
        }

        $groupedContent = [];
        foreach ($contentAreas as $identifier => $contentArea) {
            $records = [];
            foreach ($contentArea->getRecords() as $contentIndex => $content) {
                $records[$contentIndex] = $content instanceof Record
                    ? $this->contentConverter->convert($cObj->getRequest(), $content)
                    : $content;
            }

            $groupedContent[$identifier] = [
                'area' => $contentArea,
                'records' => $records,
            ];
        }

        $processedData[$targetVariableName] = $contentAreas->withUpdatedRecords($groupedContent);

        return $processedData;
    }
}
