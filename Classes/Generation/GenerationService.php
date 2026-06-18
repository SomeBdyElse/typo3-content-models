<?php

namespace SomeBdyElse\Typo3ContentModels\Generation;

use SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

class GenerationService
{
    public function __construct(
        protected TcaSchemaFactory $schemaFactory,
        protected GeneratorFactory $generatorFactory,
        protected Configuration $configuration,
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

        $sources = [];
        foreach ($schemata as $table => $tableSchema) {
            $typeSchemata = $tableSchema->getSubSchemata();
            if (count($typeSchemata) === 0) {
                $sources[] = [$table, null];
            } else {
                foreach ($typeSchemata as $type => $_) {
                    $sources[] = [$table, (string)$type];
                }
            }
        }

        $sources = array_filter(
            $sources,
            fn(array $source) => $this->configuration->getTableOverride($source[0], $source[1])->generate ?? true
        );

        foreach ($sources as [$table, $type]) {
            $generator = $this->generatorFactory->getContentModelGenerator($table, $type);
            $generatedModels[] = $generator->generateModel($table, $type);
        }

        return $generatedModels;
    }

    protected function generateCommonCode(array $generatedModels): void
    {
        $generator = $this->generatorFactory->getCommonCodeGenerator();
        $generator->generateCommonCode($generatedModels);
    }
}
