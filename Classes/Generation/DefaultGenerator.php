<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\Type;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelRegistryInterface;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
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
        protected TypeResolver $typeResolver,
        protected TcaSchemaFactory $schemaFactory,
        protected Configuration $configuration,
    ) {
    }
    
    public function generateModel(string $table, ?string $type): GeneratedModel
    {
        $tableSchema = $this->schemaFactory->get($table);
        $subSchema = $tableSchema->getSubSchema($type);

        $nameSpace = $this->namingHelper->namespaceForTable($this->configuration->targetPhpNamespace, $table);
        $namespacePath = rtrim($this->configuration->targetDirectory) . '/' . $this->namingHelper->directoryNameForTable($table);
        
        $modelDirectory = GeneralUtility::getFileAbsFileName($namespacePath);
        if (!is_dir($modelDirectory)) {
            GeneralUtility::mkdir_deep($modelDirectory);
        }

        $modelNamespace = new PhpNamespace($nameSpace);
        $contentModelInterface = ContentModelInterface::class;
        $contentModelAttribute = '\\' . $this->configuration->targetPhpNamespace . '\\ContentModel';
        $modelNamespace->addUse($contentModelInterface);
        $modelNamespace->addUse($contentModelAttribute);
        $modelNamespace->addUse(Record::class);

        $classNameOverride = $this->configuration->overrides[$table][$type]?->className;
        $className = $classNameOverride ?? $this->namingHelper->classNameForType($table, $type);
        $model = $modelNamespace->addClass($className);
        $model->addAttribute(ltrim($contentModelAttribute, '\\'), [
            'table' => $table,
            'type' => $type,
        ]);

        $staticBody = '$arguments = [];' . "\n";
        $model->addImplement($contentModelInterface);
        $constructor = $model->addMethod('__construct');
        foreach($subSchema->getFields() as $field) {
            if ($this->isSystemField($tableSchema, $field)) {
                continue;
            }
            if ($field->getConfiguration()['type'] === 'none') {
                continue;
            }
            $fieldName = $field->getName();
            $parameter = $constructor->addPromotedParameter($fieldName);
            $parameter->setPublic();
            $parameter->setReadOnly();
            $parameterType = Type::fromString($this->typeResolver->getTypeForField($table, $type, $subSchema, $field));
            if ($parameterType->isClass()) {
                $modelNamespace->addUse(\Nette\PhpGenerator\Type::nullable((string)$parameterType, false));
            }
            
            $parameter->setType((string) $parameterType);

            $nullFallback = $parameterType->getSingleName() === LazyRecordCollection::class ? ' new \\' . LazyRecordCollection::class . '(null, fn() => [])' : null;
            $nullFallbackString = isset($nullFallback) ? " ?? {$nullFallback}" : '';

            $staticBody .= "\$arguments['{$fieldName}'] = \$record->get('{$fieldName}'){$nullFallbackString};\n";
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
        $this->generateContentModelAttribute();
        $this->generateContentModelRegistry($generatedModels);
    }

    protected function generateContentModelAttribute(): void
    {
        $namespace = new PhpNamespace($this->configuration->targetPhpNamespace);
        $namespace->addUse(\Attribute::class);
        $attribute = $namespace->addClass('ContentModel');
        $attribute->setFinal();
        $attribute->setReadOnly();
        $attribute->addAttribute(\Attribute::class);
        $constructor = $attribute->addMethod('__construct');
        $constructor->addPromotedParameter('table')
            ->setPublic()
            ->setType('string');
        $constructor->addPromotedParameter('type')
            ->setPublic()
            ->setNullable()
            ->setType('string');

        $this->writePhpFile('ContentModel.php', $namespace);
    }

    /**
     * @param list<GeneratedModel> $generatedModels
     */
    protected function generateContentModelRegistry(array $generatedModels): void
    {
        $modelMap = [];
        foreach ($generatedModels as $generatedModel) {
            $modelMap[$generatedModel->table][(string) $generatedModel->type] = $generatedModel->className;
        }
        ksort($modelMap);
        foreach ($modelMap as &$modelsByType) {
            ksort($modelsByType);
        }
        unset($modelsByType);

        $namespace = new PhpNamespace($this->configuration->targetPhpNamespace);
        $namespace->addUse(ContentModelRegistryInterface::class);
        $registry = $namespace->addClass('ContentModelRegistry');
        $registry->setFinal();
        $registry->addImplement(ContentModelRegistryInterface::class);

        $property = $registry->addProperty('modelClassNamesByTableAndType', $modelMap);
        $property->setPrivate();
        $property->setType('array');

        $getModelClassName = $registry->addMethod('getModelClassName');
        $getModelClassName->setPublic();
        $getModelClassName->setReturnType('?string');
        $getModelClassName->addParameter('table')->setType('string');
        $getModelClassName->addParameter('type')->setNullable()->setType('string');
        $getModelClassName->setBody("return \$this->modelClassNamesByTableAndType[\$table][(string) \$type] ?? null;");

        $getContentModel = $registry->addMethod('getContentModel');
        $getContentModel->setPublic();
        $getContentModel->setReturnType($this->configuration->targetPhpNamespace . '\\ContentModel');
        $getContentModel->setReturnNullable();
        $getContentModel->addParameter('className')->setType('string');
        $getContentModel->setBody(<<<'PHP'
            $attributes = (new \ReflectionClass($className))->getAttributes(ContentModel::class);

            return $attributes === [] ? null : $attributes[0]->newInstance();
            PHP);

        $all = $registry->addMethod('all');
        $all->setPublic();
        $all->setReturnType('array');
        $all->setBody("return \$this->modelClassNamesByTableAndType;");

        $this->writePhpFile('ContentModelRegistry.php', $namespace);
    }

    protected function writePhpFile(string $filename, PhpNamespace $namespace): void
    {
        $directory = GeneralUtility::getFileAbsFileName($this->configuration->targetDirectory);
        GeneralUtility::mkdir_deep($directory);
        $file = new PhpFile();
        $file->addNamespace($namespace);
        $path = $directory . '/' . $filename;
        file_put_contents($path, $file);
    }
}
