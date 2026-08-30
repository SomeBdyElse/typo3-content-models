<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use SomeBdyElse\Typo3ContentModels\Rendering\ContentModelsProcessor\ContentAreaCollectionProcessor;
use SomeBdyElse\Typo3ContentModels\Rendering\ContentModelsProcessor\ContentArrayProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

class ContentModelsProcessor implements DataProcessorInterface
{
    private const CONTENT_AREA_COLLECTION_CLASS = 'TYPO3\\CMS\\Core\\Page\\ContentAreaCollection';

    public function __construct(
        protected ContentArrayProcessor $contentArrayProcessor,
        protected ContentAreaCollectionProcessor $contentAreaCollectionProcessor,
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
        if (! (is_array($contentAreas) || is_a($contentAreas, self::CONTENT_AREA_COLLECTION_CLASS))) {
            throw new \InvalidArgumentException(sprintf(
                'Expected processed data "%s" to contain an array or %s, got %s.',
                $sourceVariableName,
                self::CONTENT_AREA_COLLECTION_CLASS,
                get_debug_type($contentAreas),
            ), 1764508392);
        }

        $processedData[$targetVariableName] = is_array($contentAreas)
            ? $this->contentArrayProcessor->process($cObj, $contentAreas)
            : $this->contentAreaCollectionProcessor->process($cObj, $contentAreas);

        return $processedData;
    }
}
