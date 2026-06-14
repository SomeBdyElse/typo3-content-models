<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

use TYPO3\CMS\Core\Schema\SchemaCollection;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

class GenerationService
{
    public function __construct(
        protected TcaSchemaFactory $schemaFactory,
        protected GeneratorFactory $generatorFactory,
    ) {
    }

    public function generateAll(): void
    {
        $generatedModels = $this->generateContentModels();
        $this->generateCommonCode($generatedModels);

    }

    protected function generateContentModels(): array
    {
        $generatedModels = [];
        $schemata = $this->schemaFactory->all();

        foreach ($schemata as $tableName => $tableSchema) {
            $subSchemata = $tableSchema->getSubSchemata();
            foreach ($subSchemata as $type => $subSchema) {
                $generator = $this->generatorFactory->getContentModelGenerator($tableName, $type);
                $generatedModels[] = $generator->generateModel($tableName, $type);
            }
        }

        return $generatedModels;
    }

    protected function generateCommonCode(array $generatedModels): void
    {
        $generator = $this->generatorFactory->getCommonCodeGenerator();
        $generator->generateCommonCode($generatedModels);
    }
}
