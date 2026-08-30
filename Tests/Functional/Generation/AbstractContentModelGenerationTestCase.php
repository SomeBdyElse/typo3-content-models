<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Tests\Functional\Generation;

use SomeBdyElse\Typo3ContentModels\Generation\GenerateModelsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractContentModelGenerationTestCase extends FunctionalTestCase
{
    private const TARGET_DIRECTORY_ENVIRONMENT_VARIABLE = 'CONTENT_MODELS_TEST_TARGET_DIRECTORY';

    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
    ];

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/content_models',
    ];

    protected function tearDown(): void
    {
        putenv(self::TARGET_DIRECTORY_ENVIRONMENT_VARIABLE);

        parent::tearDown();
    }

    /**
     * @return list<string>
     */
    protected function generateContentModelsFromFixture(string $fixtureFileName): array
    {
        $targetDirectory = $this->prepareProjectConfiguration($fixtureFileName);

        $commandTester = new CommandTester(GeneralUtility::makeInstance(GenerateModelsCommand::class));
        self::assertSame(Command::SUCCESS, $commandTester->execute([]), $commandTester->getDisplay());

        $generatedFiles = $this->collectGeneratedPhpFiles($targetDirectory);
        self::assertNotEmpty($generatedFiles, 'No content model PHP files were generated.');
        $this->assertPhpFilesCanBeParsed($generatedFiles);

        return $generatedFiles;
    }

    protected function assertGeneratedFileExists(array $generatedFiles, string $relativeFilePath): void
    {
        self::assertContains(
            $this->normalizePath($relativeFilePath),
            array_map([$this, 'relativeGeneratedPath'], $generatedFiles),
            sprintf('Generated file "%s" was not found.', $relativeFilePath),
        );
    }

    protected function assertAtLeastOneGeneratedFileContains(array $generatedFiles, string $expectedContent): void
    {
        foreach ($generatedFiles as $generatedFile) {
            $content = file_get_contents($generatedFile);
            if ($content !== false && str_contains($content, $expectedContent)) {
                self::assertTrue(true);
                return;
            }
        }

        self::fail(sprintf('No generated file contains "%s".', $expectedContent));
    }

    private function prepareProjectConfiguration(string $fixtureFileName): string
    {
        $targetDirectory = Environment::getVarPath()
            . '/tests/generated-content-models/'
            . str_replace('\\', '_', static::class);
        GeneralUtility::rmdir($targetDirectory, true);
        GeneralUtility::mkdir_deep($targetDirectory);

        putenv(self::TARGET_DIRECTORY_ENVIRONMENT_VARIABLE . '=' . $targetDirectory);

        $systemConfigPath = Environment::getConfigPath() . '/system';
        GeneralUtility::mkdir_deep($systemConfigPath);

        $sourceFile = dirname(__DIR__) . '/Fixtures/Configuration/' . $fixtureFileName;
        self::assertFileExists($sourceFile);
        self::assertTrue(
            copy($sourceFile, $systemConfigPath . '/content_models.yaml'),
            sprintf('Could not copy content model test fixture "%s".', $fixtureFileName),
        );

        return $targetDirectory;
    }

    /**
     * @return list<string>
     */
    private function collectGeneratedPhpFiles(string $targetDirectory): array
    {
        if (!is_dir($targetDirectory)) {
            return [];
        }

        $generatedFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($targetDirectory, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $generatedFiles[] = $file->getPathname();
            }
        }

        sort($generatedFiles);

        return $generatedFiles;
    }

    /**
     * @param list<string> $generatedFiles
     */
    private function assertPhpFilesCanBeParsed(array $generatedFiles): void
    {
        foreach ($generatedFiles as $generatedFile) {
            try {
                token_get_all((string)file_get_contents($generatedFile), TOKEN_PARSE);
            } catch (\ParseError $parseError) {
                self::fail(sprintf(
                    'Generated file "%s" contains invalid PHP: %s',
                    $this->relativeGeneratedPath($generatedFile),
                    $parseError->getMessage(),
                ));
            }
        }
    }

    private function relativeGeneratedPath(string $path): string
    {
        return $this->normalizePath(str_replace(
            Environment::getVarPath() . '/tests/generated-content-models/' . str_replace('\\', '_', static::class) . '/',
            '',
            $path,
        ));
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
