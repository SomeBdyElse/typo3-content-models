<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use TYPO3\CMS\Core\Domain\Record;

class GenericContentModel implements ContentModelInterface, \JsonSerializable
{
    public function __construct(
        protected Record $record,
        protected array $properties,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return array_merge(
            ['type' => $this->record->getRecordType()],
            $this->properties,
        );
    }
}
