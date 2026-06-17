<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration;

use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

final readonly class HandlerResolver
{
    /**
     * @param iterable<HandlerInterface> $handlers
     */
    public function __construct(
        private iterable $handlers,
    ) {
    }

    public function generate(
        string $table,
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $field,
    ): GeneratedField {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($field)) {
                return $handler->generate($table, $type, $subSchema, $field);
            }
        }

        throw new \LogicException(sprintf(
            'No field generation strategy supports field "%s" of type "%s".',
            $field->getName(),
            $field->getType(),
        ));
    }
}
