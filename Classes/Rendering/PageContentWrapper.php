<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\AfterContentHasBeenFetchedEvent;

#[AsEventListener]
class PageContentWrapper
{
    public function __construct(
        protected ContentConverter $contentConverter,
    ) {
    }

    public function __invoke(AfterContentHasBeenFetchedEvent $event): void
    {
        $newGroupedContent = [];
        foreach ($event->groupedContent as $groupIndex => $group) {
            $newGroupedContent[$groupIndex] = [];
            foreach ($group['records'] as $contentIndex => $content) {
                $newGroupedContent[$groupIndex][$contentIndex] = $this->contentConverter->convert($event->request, $content);
            }
        }
        $event->groupedContent = $newGroupedContent;
    }
}
