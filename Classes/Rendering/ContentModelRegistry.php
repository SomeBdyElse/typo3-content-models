<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelRegistryInterface;

final class ContentModelRegistry implements ContentModelRegistryInterface
{
    /**
     * @var array<string, array<array-key, class-string<ContentModelInterface>>>
     */
    private array $modelClassNamesByTableAndType = [];

    public function getModelClassName(string $table, ?string $type): ?string
    {
        return $this->modelClassNamesByTableAndType[$table][(string) $type] ?? null;
    }

    public function all(): array
    {
        return $this->modelClassNamesByTableAndType;
    }

    /**
     * @param class-string<ContentModelInterface> $className
     */
    public function registerContentModel(string $table, ?string $type, string $className): void
    {
        if (!is_a($className, ContentModelInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Content model class "%s" must implement %s.',
                $className,
                ContentModelInterface::class,
            ));
        }

        $this->modelClassNamesByTableAndType[$table][(string) $type] = $className;
    }
}
