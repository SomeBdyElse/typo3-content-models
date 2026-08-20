<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\Generators;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\Type;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModel;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\FieldGenerator;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\GeneratedModel;
use SomeBdyElse\Typo3ContentModels\Generation\ModelGeneratorInterface;
use SomeBdyElse\Typo3ContentModels\Generation\NamingHelper;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Schema\Capability\LanguageAwareSchemaCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractDtoGenerator implements ModelGeneratorInterface
{
    public function __construct(
        protected NamingHelper $namingHelper,
        protected FieldGenerator $fieldGenerator,
        protected TcaSchemaFactory $schemaFactory,
        protected Configuration $configuration,
    ) {
    }

    public function generateModel(string $table, ?string $type): GeneratedModel
    {
        $tableSchema = $this->schemaFactory->get($table);
        $schema = $type === null ? $tableSchema : $tableSchema->getSubSchema($type);

        $nameSpace = $this->namingHelper->namespaceForTable($this->configuration->targetPhpNamespace, $table);
        $namespacePath = rtrim($this->configuration->targetDirectory) . '/' . $this->namingHelper->directoryNameForTable($table);

        $modelDirectory = GeneralUtility::getFileAbsFileName($namespacePath);
        if (!is_dir($modelDirectory)) {
            GeneralUtility::mkdir_deep($modelDirectory);
        }

        $modelNamespace = new PhpNamespace($nameSpace);
        $modelNamespace->addUse(ContentModelInterface::class);
        $modelNamespace->addUse(ContentModel::class);
        $modelNamespace->addUse(Record::class);

        $className = $this->namingHelper->classNameForType($table, $type);
        $model = $modelNamespace->addClass($className);
        $model->addAttribute(ContentModel::class, [
            'table' => $table,
            'type' => $type,
        ]);
        $model->addImplement(ContentModelInterface::class);

        $constructor = $this->createConstructor($model);

        $generatedFields = [];
        foreach ($schema->getFields() as $field) {
            if ($this->isSystemField($tableSchema, $field)) {
                continue;
            }
            if ($field->getConfiguration()['type'] === 'none') {
                continue;
            }

            $generatedField = $this->fieldGenerator->generate($table, $type, $schema, $field);
            $generatedFields[] = $generatedField;
            $fieldType = Type::fromString($generatedField->nativeType);
            $this->addGeneratedField($model, $constructor, $generatedField, $fieldType);
            foreach ($generatedField->uses as $use) {
                $modelNamespace->addUse($use);
            }
        }

        $fullClassName = "\\{$nameSpace}\\{$className}";
        $this->addFromRecordMethod($model, $fullClassName, $generatedFields);

        $file = new PhpFile();
        $file->addNamespace($modelNamespace);
        $filename = $className . '.php';
        $path = $modelDirectory . '/' . $filename;
        file_put_contents($path, $file);

        return new GeneratedModel(
            table: $table,
            type: $type,
            className: ltrim($fullClassName, '\\'),
            class: $model,
        );
    }

    abstract protected function createConstructor(ClassType $model): Method;

    abstract protected function addGeneratedField(
        ClassType $model,
        Method $constructor,
        GeneratedField $generatedField,
        Type $fieldType,
    ): void;

    /**
     * @param list<GeneratedField> $generatedFields
     */
    abstract protected function buildFromRecordBody(array $generatedFields): string;

    /**
     * @param list<GeneratedField> $generatedFields
     */
    protected function addFromRecordMethod(ClassType $model, string $fullClassName, array $generatedFields): void
    {
        $fromRecord = $model->addMethod('fromRecord');
        $fromRecord
            ->setPublic()
            ->setStatic()
            ->setReturnType($fullClassName)
        ;
        $parameter = $fromRecord->addParameter('record');
        $parameter->setType(Record::class);
        $fromRecord->setBody($this->buildFromRecordBody($generatedFields));
    }

    protected function isSystemField(TcaSchema $tableSchema, FieldTypeInterface $field): bool
    {
        $systemFields = [];

        if ($tableSchema->isLanguageAware()) {
            $languageCapability = $tableSchema->getCapability(TcaSchemaCapability::Language);
            $systemFields[] = $languageCapability->getLanguageField()->getName();
            $systemFields[] = $languageCapability->getTranslationOriginPointerField()->getName();
            $systemFields[] = $languageCapability->hasTranslationSourceField() ? $languageCapability->getTranslationSourceField()->getName() : '';
        }
        foreach (TcaSchemaCapability::getSystemCapabilities() as $capability) {
            try {
                $capability = $tableSchema->getCapability($capability);
            } catch (\Throwable) {
                continue;
            }

            if ($capability instanceof LanguageAwareSchemaCapability) {
                $systemFields[] = $capability->getLanguageField();
                $systemFields[] = $capability->getTranslationSourceField();
            }
            $systemFields[] = $capability->getFieldName();
        }

        return in_array($field->getName(), $systemFields);
    }
}
