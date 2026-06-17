<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Contract;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ContentModel
{
    public const TAG = 'content_models.content_model';

    public function __construct(
        public readonly string $table,
        public readonly ?string $type = null,
    ) {
    }
}
