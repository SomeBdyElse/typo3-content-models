<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsFieldGenerationHandler
{
    public const TAG_NAME = 'content_models.field_generation.handler';

    /**
     * @param non-empty-string $identifier
     * @param list<non-empty-string> $before
     * @param list<non-empty-string> $after
     */
    public function __construct(
        public string $identifier,
        public array $before = [],
        public array $after = [],
    ) {
    }
}
