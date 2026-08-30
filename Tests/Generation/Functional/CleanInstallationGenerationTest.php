<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Tests\Generation\Functional;

use PHPUnit\Framework\Attributes\Test;

final class CleanInstallationGenerationTest extends AbstractContentModelGenerationTestCase
{
    #[Test]
    public function generatesContentModelsForCleanInstallation(): void
    {
        $generatedFiles = $this->generateContentModelsFromFixture('clean-content-models.yaml');

        $this->assertGeneratedFileExists($generatedFiles, 'Pages/DefaultPage.php');
        $this->assertGeneratedFileExists($generatedFiles, 'Content/TextContentModel.php');
        $this->assertAtLeastOneGeneratedFileContains($generatedFiles, "#[ContentModel(table: 'pages', type: '1')]");
        $this->assertAtLeastOneGeneratedFileContains($generatedFiles, "#[ContentModel(table: 'tt_content', type: 'text')]");
    }
}
