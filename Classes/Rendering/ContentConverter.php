<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use Psr\Http\Message\ServerRequestInterface;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelFactoryInterface;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use TYPO3\CMS\Core\Domain\Record;

class ContentConverter
{
    public function __construct(
        protected ContentModelFactoryInterface $contentModelFactory,
    ) {
    }

    public function convert(ServerRequestInterface $request, Record $record): ContentModelInterface
    {
        return ($this->contentModelFactory)($record);
    }
}
