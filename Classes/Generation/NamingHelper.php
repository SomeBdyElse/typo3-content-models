<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation;

use Nette\PhpGenerator\Helpers;
use SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration;

class NamingHelper
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    public function namespaceForTable(string $rootNamespace, string $table): string
    {
        return trim($rootNamespace, '\\') . '\\' . $this->tableNamespaceSegment($table);
    }

    public function classNameForType(string $table, ?string $type): string
    {
        $className = $this->configuration->getTableConfiguration($table, $type)['className'] ?? null;
        if (isset($className)) {
            return $className;
        }

        $name = $type ?? $table;
        if (is_numeric($name)) {
            return 'Type' . $name;
        }

        $result = $this->identifierSegment($name, 'Content');
        if (is_numeric($result[0])) {
            $result = 'Type' . $result;
        }
        if (!Helpers::isIdentifier($result)) {
            $result = 'Content';
        }

        if (Helpers::isIdentifier($result) || isset(Helpers::Keywords[strtolower($result)])) {
            $result .= 'ContentModel';
        }

        return $result;
    }

    public function directoryNameForTable(string $table): string
    {
        return $this->tableNamespaceSegment($table);
    }

    public function getterNameForField(string $field): string
    {
        return 'get' . $this->identifierSegment($field, 'Field');
    }

    private function tableNamespaceSegment(string $table): string
    {
        $normalizedTable = preg_replace('/^(tt_|tx_)/', '', $table) ?? $table;
        $result = $this->identifierSegment($normalizedTable, 'Table');

        if ($result === '') {
            return 'Table';
        }

        if (is_numeric($result[0]) || isset(Helpers::Keywords[strtolower($result)])) {
            return 'Table' . $result;
        }

        return $result;
    }

    private function identifierSegment(string $value, string $fallback): string
    {
        $identifier = preg_replace('/[^a-zA-Z0-9]+/', ' ', $value) ?? '';
        $identifier = str_replace(' ', '', ucwords(trim($identifier)));

        return $identifier === '' ? $fallback : $identifier;
    }
}
