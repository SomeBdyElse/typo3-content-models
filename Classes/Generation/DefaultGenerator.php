<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\Type;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModel;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerResolver;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Schema\Capability\LanguageAwareSchemaCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DefaultGenerator implements ModelGenerator, CommonCodeGenerator
{
    public function __construct(
        protected NamingHelper $namingHelper,
        protected HandlerResolver $fieldHandlerResolver,
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
        $contentModelInterface = ContentModelInterface::class;
        $modelNamespace->addUse($contentModelInterface);
        $modelNamespace->addUse(ContentModel::class);
        $modelNamespace->addUse(Record::class);

        $className = $this->namingHelper->classNameForType($table, $type);
        $model = $modelNamespace->addClass($className);
        $model->addAttribute(ContentModel::class, [
            'table' => $table,
            'type' => $type,
        ]);

        $staticBody = '$arguments = [];' . "\n";
        $model->addImplement($contentModelInterface);
        $constructor = $model->addMethod('__construct');

        $uidParameter = $constructor->addPromotedParameter('uid');
        $uidParameter->setType('int');
        $staticBody .= "\$arguments['uid'] = \$record->get('uid');\n";

        foreach ($schema->getFields() as $field) {
            if ($this->isSystemField($tableSchema, $field)) {
                continue;
            }
            if ($field->getConfiguration()['type'] === 'none') {
                continue;
            }
            $generatedField = $this->fieldHandlerResolver->generate($table, $type, $schema, $field);
            foreach ($generatedField->uses as $use) {
                $modelNamespace->addUse($use);
            }

            $fieldName = $generatedField->name;
            $parameter = $constructor->addPromotedParameter($fieldName);
            $parameter->setPublic();
            $parameter->setReadOnly();
            if ($generatedField->phpDocType !== null) {
                $parameter->setComment('@var ' . $generatedField->phpDocType . ' $' . $fieldName);
            }
            $parameterType = Type::fromString($generatedField->nativeType);
            if ($parameterType->isClass()) {
                $modelNamespace->addUse(\Nette\PhpGenerator\Type::nullable((string)$parameterType, false));
            }
            
            $parameter->setType((string) $parameterType);

            $staticBody .= "\$arguments['{$fieldName}'] = {$generatedField->fromRecordExpression};\n";
        }
        $fullClassName = "\\{$nameSpace}\\{$className}";
        $staticBody .= "return new self(...\$arguments);";

        $fromRecord = $model->addMethod('fromRecord');
        $fromRecord
            ->setPublic()
            ->setStatic()
            ->setReturnType($fullClassName)
        ;
        $parameter = $fromRecord->addParameter('record');
        $parameter->setType(Record::class);
        $fromRecord->setBody($staticBody);

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

    public function isSystemField(TcaSchema $tableSchema, FieldTypeInterface $field): bool
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

    /**
     * @param list<GeneratedModel> $generatedModels
     */
    public function generateCommonCode(array $generatedModels = []): void
    {
    }
}
