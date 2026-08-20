<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\Generators;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\Utils\Type;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use TYPO3\CMS\Core\Domain\Record;

class LazyDtoGenerator extends AbstractDtoGenerator
{
    protected function createConstructor(ClassType $model): Method
    {
        $constructor = $model->addMethod('__construct');
        $uidParameter = $constructor->addPromotedParameter('uid');
        $uidParameter->setPublic();
        $uidParameter->setType('int');

        $recordParameter = $constructor->addPromotedParameter('record');
        $recordParameter->setPrivate();
        $recordParameter->setReadOnly();
        $recordParameter->setType(Record::class);

        return $constructor;
    }

    protected function addGeneratedField(
        ClassType $model,
        Method $constructor,
        GeneratedField $generatedField,
        Type $fieldType,
    ): void {
        $method = $model->addMethod($this->namingHelper->getterNameForField($generatedField->name));
        $method->setPublic();
        if ($generatedField->phpDocType !== null) {
            $method->setComment('@return ' . $generatedField->phpDocType);
        }

        $method->setReturnType((string)$fieldType);
        $method->setBody("\$record = \$this->record;\nreturn {$generatedField->fromRecordExpression};");
    }

    /**
     * @param list<GeneratedField> $generatedFields
     */
    protected function buildFromRecordBody(array $generatedFields): string
    {
        return "return new self(\$record->get('uid'), \$record);";
    }
}
