<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Contract;

interface ContentModelRegistryInterface
{
    /**
     * @return class-string<ContentModelInterface>|null
     */
    public function getModelClassName(string $table, ?string $type): ?string;

    /**
     * @return array<string, array<array-key, class-string<ContentModelInterface>>>
     */
    public function all(): array;
}
