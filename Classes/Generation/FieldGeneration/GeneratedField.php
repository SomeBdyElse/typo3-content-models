<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration;

final readonly class GeneratedField
{
    /**
     * @param list<class-string> $uses
     */
    public function __construct(
        public string $name,
        public string $nativeType,
        public string $fromRecordExpression,
        public array $uses = [],
        public ?string $phpDocType = null,
    ) {
    }
}
