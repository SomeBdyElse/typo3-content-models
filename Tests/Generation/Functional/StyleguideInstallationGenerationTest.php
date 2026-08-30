<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Tests\Generation\Functional;

use PHPUnit\Framework\Attributes\Test;

final class StyleguideInstallationGenerationTest extends AbstractContentModelGenerationTestCase
{
    protected array $coreExtensionsToLoad = [
        'felogin',
        'fluid_styled_content',
        'form',
        'indexed_search',
        'seo',
    ];

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/content_models',
        'typo3conf/ext/styleguide',
    ];

    #[Test]
    public function generatesContentModelsForStyleguideInstallation(): void
    {
        $generatedFiles = $this->generateContentModelsFromFixture('styleguide-content-models.yaml');

        $this->assertGeneratedFileExists($generatedFiles, 'Pages/DefaultPage.php');
        $this->assertGeneratedFileExists($generatedFiles, 'Content/TextContentModel.php');
        $this->assertAtLeastOneGeneratedFileContains($generatedFiles, "#[ContentModel(table: 'tx_styleguide_");
    }
}
