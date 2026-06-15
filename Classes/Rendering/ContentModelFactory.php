<?php

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModelFactoryInterface;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelRegistryInterface;
use SomeBdyElse\Typo3ContentModels\Generation\Configuration;
use TYPO3\CMS\Core\Domain\Record;

class ContentModelFactory implements ContentModelFactoryInterface
{
    private ?ContentModelRegistryInterface $registry = null;

    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    public function __invoke(Record $record): ContentModelInterface
    {
        $modelClassName = $this->getRegistry()?->getModelClassName($record->getMainType(), $record->getRecordType());
        if ($modelClassName === null || !method_exists($modelClassName, 'fromRecord')) {
            return new GenericContentModel($record, $record->toArray());
        }

        return $modelClassName::fromRecord($record);
    }

    private function getRegistry(): ?ContentModelRegistryInterface
    {
        if ($this->registry instanceof ContentModelRegistryInterface) {
            return $this->registry;
        }

        $registryClassName = $this->configuration->targetPhpNamespace . '\\ContentModelRegistry';
        if (!class_exists($registryClassName)) {
            return null;
        }

        $registry = new $registryClassName();
        if (!$registry instanceof ContentModelRegistryInterface) {
            return null;
        }

        return $this->registry = $registry;
    }
}
