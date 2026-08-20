<?php

namespace SomeBdyElse\Typo3ContentModels\Generation\Exception;

class NoGeneratorConfiguredException extends \RuntimeException
{
    public function __construct(
        public string $table,
        public ?string $type
    ) {
        $message = "No generator configured for table '{table}'";
        if (isset($this->type)) {
            $message .= " with type '{$this->type}'";
        }
        $message .= '.';

        $this->message = $message;
    }
}
