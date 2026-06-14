<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Contract;

use TYPO3\CMS\Core\Domain\Record;

interface ContentModelFactoryInterface
{
    public function __invoke(Record $record): ContentModelInterface;
}
