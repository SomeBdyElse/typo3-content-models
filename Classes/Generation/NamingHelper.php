<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation;

use Nette\PhpGenerator\Helpers;

class NamingHelper
{
    public function namespaceForTable(string $rootNamespace, string $table): string
    {
        return trim($rootNamespace, '\\') . '\\' . $this->tableNamespaceSegment($table);
    }

    public function classNameForType(string $table, string $type): string
    {
        if (is_numeric($type)) {
            return 'Type' . $type;
        }
        
        $result = ucfirst(str_replace('_', '', ucwords($type, '_-')));

        if (Helpers::isIdentifier($result) || isset(Helpers::Keywords[strtolower($result)])) {
            $result .= 'ContentModel';
        }

        return $result;
    }

    public function directoryNameForTable(string $table): string
    {
        return $this->tableNamespaceSegment($table);
    }

    private function tableNamespaceSegment(string $table): string
    {
        $normalizedTable = preg_replace('/^(tt_|tx_)/', '', $table) ?? $table;
        $result = str_replace('_', '', ucwords($normalizedTable, '_-'));

        if ($result === '') {
            return 'Table';
        }

        if (is_numeric($result[0]) || isset(Helpers::Keywords[strtolower($result)])) {
            return 'Table' . $result;
        }

        return $result;
    }
}
