<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering\ContentModelsProcessor;

use SomeBdyElse\Typo3ContentModels\Rendering\ContentConverter;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Page\ContentAreaCollection;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ContentAreaCollectionProcessor
{
    public function __construct(
        protected ContentConverter $contentConverter,
    ) {
    }

    public function process(ContentObjectRenderer $cObj, ContentAreaCollection $contentAreas): ContentAreaCollection
    {
        $processedContentAreas = [];
        foreach ($contentAreas as $identifier => $contentArea) {
            $records = [];
            foreach ($contentArea as $contentIndex => $content) {
                $records[$contentIndex] = $content instanceof Record
                    ? $this->contentConverter->convert($cObj->getRequest(), $content)
                    : $content;
            }

            $processedContentAreas[$identifier] = $contentArea->withRecords($records);
        }

        return new ContentAreaCollection($processedContentAreas);
    }
}
