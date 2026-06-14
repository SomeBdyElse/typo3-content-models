<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation;

use Nette\PhpGenerator\ClassType;

final readonly class GeneratedModel
{
    public function __construct(
        public string $table,
        public ?string $type,
        public string $className,
        public ClassType $class,
    ) {
    }
}
