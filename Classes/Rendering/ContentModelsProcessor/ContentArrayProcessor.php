<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering\ContentModelsProcessor;

use SomeBdyElse\Typo3ContentModels\Rendering\ContentConverter;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ContentArrayProcessor
{
    public function __construct(
        protected ContentConverter $contentConverter,
    ) {
    }

    public function process(ContentObjectRenderer $cObj, array $contentAreas): array
    {
        foreach ($contentAreas as $identifier => $contentArea) {
            if (!is_array($contentArea) || !is_iterable($contentArea['records'] ?? null)) {
                continue;
            }

            foreach ($contentArea['records'] as $contentIndex => $content) {
                if ($content instanceof Record) {
                    $contentAreas[$identifier]['records'][$contentIndex] = $this->contentConverter->convert(
                        $cObj->getRequest(),
                        $content,
                    );
                }
            }
        }

        return $contentAreas;
    }
}
