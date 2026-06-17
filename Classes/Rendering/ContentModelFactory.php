<?php

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModelFactoryInterface;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelRegistryInterface;
use TYPO3\CMS\Core\Domain\Record;

class ContentModelFactory implements ContentModelFactoryInterface
{
    public function __construct(
        private readonly ContentModelRegistryInterface $registry,
    ) {
    }

    public function __invoke(Record $record): ContentModelInterface
    {
        $modelClassName = $this->registry->getModelClassName($record->getMainType(), $record->getRecordType());
        if ($modelClassName === null || !method_exists($modelClassName, 'fromRecord')) {
            return new GenericContentModel($record, $record->toArray());
        }

        return $modelClassName::fromRecord($record);
    }
}
