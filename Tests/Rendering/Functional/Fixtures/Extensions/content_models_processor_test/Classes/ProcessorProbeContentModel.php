<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModelsProcessorTest;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModel;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use TYPO3\CMS\Core\Domain\Record;

#[ContentModel(table: 'tt_content', type: 'processor_probe')]
final readonly class ProcessorProbeContentModel implements ContentModelInterface
{
    public function __construct(
        private Record $record,
    ) {
    }

    public function getHeader(): string
    {
        return $this->record->get('header');
    }

    public function getProcessorProbeMarker(): string
    {
        return 'processor-probe-content-model:' . $this->record->get('uid');
    }

    public static function fromRecord(Record $record): self
    {
        return new self($record);
    }
}
