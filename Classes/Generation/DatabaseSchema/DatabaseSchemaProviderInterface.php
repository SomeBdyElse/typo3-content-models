<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\DatabaseSchema;

use Doctrine\DBAL\Schema\Column;

interface DatabaseSchemaProviderInterface
{
    public function getColumn(string $table, string $field): ?Column;
}
