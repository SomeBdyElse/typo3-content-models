<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\Handler;

use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use SomeBdyElse\Typo3ContentModels\Generation\Configuration\Configuration;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\GeneratedField;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use SomeBdyElse\Typo3ContentModels\Generation\NamingHelper;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\StaticSelectFieldType;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

final readonly class StaticSelectSingleEnum implements HandlerInterface
{
    public function __construct(
        private NamingHelper $namingHelper,
        private Configuration $configuration,
    ) {
    }

    public function supports(FieldTypeInterface $field): bool
    {
        if (!$field instanceof StaticSelectFieldType) {
            return false;
        }
        if ((string)($field->getConfiguration()['renderType'] ?? '') !== 'selectSingle') {
            return false;
        }

        return $this->hasValidEnumItems($field);
    }

    public function generate(
        string $table,
        ?string $type,
        TcaSchema $subSchema,
        FieldTypeInterface $field,
    ): GeneratedField {
        $enumClassName = $this->enumClassName($table, $type, $subSchema, $field);
        $namespace = $this->namingHelper->namespaceForTable($this->configuration->targetPhpNamespace, $table);
        $fullEnumClassName = $namespace . '\\' . $enumClassName;

        $cases = $this->enumCases($field);
        $enumType = $this->enumType($cases);
        $this->writeEnum($namespace, $enumClassName, $enumType, $cases, $table);

        $fieldName = $field->getName();
        $recordValueExpression = $enumType === 'int'
            ? "(int)\$record->get('{$fieldName}')"
            : "(string)\$record->get('{$fieldName}')";

        return new GeneratedField(
            name: $fieldName,
            nativeType: $fullEnumClassName,
            fromRecordExpression: "\\{$fullEnumClassName}::from({$recordValueExpression})",
            uses: [$fullEnumClassName],
        );
    }

    /**
     * @return array<string, int|string>
     */
    private function enumCases(StaticSelectFieldType $field): array
    {
        $cases = [];
        $usedCaseNames = [];
        $usedValues = [];
        $isIntBackedEnum = $this->isIntBackedEnum($field);
        foreach ($field->getItems() as $item) {
            $value = $item->getValue();
            if (!is_string($value) && !is_int($value)) {
                return [];
            }

            $backingValue = $value;
            $backingValueKey = get_debug_type($backingValue) . ':' . $backingValue;
            if (isset($usedValues[$backingValueKey])) {
                return [];
            }
            $usedValues[$backingValueKey] = true;

            $caseName = $this->caseName($field, $item->getLabel(), $backingValue, $isIntBackedEnum);
            $deduplicatedCaseName = $caseName;
            $index = 2;
            while (isset($usedCaseNames[$deduplicatedCaseName])) {
                $deduplicatedCaseName = $caseName . $index;
                ++$index;
            }

            $usedCaseNames[$deduplicatedCaseName] = true;
            $cases[$deduplicatedCaseName] = $backingValue;
        }

        return $cases;
    }

    private function hasValidEnumItems(StaticSelectFieldType $field): bool
    {
        $usedValues = [];
        foreach ($field->getItems() as $item) {
            $value = $item->getValue();
            if (!is_string($value) && !is_int($value)) {
                return false;
            }

            $backingValueKey = get_debug_type($value) . ':' . $value;
            if (isset($usedValues[$backingValueKey])) {
                return false;
            }
            $usedValues[$backingValueKey] = true;
        }

        return $usedValues !== [];
    }

    private function isIntBackedEnum(StaticSelectFieldType $field): bool
    {
        foreach ($field->getItems() as $item) {
            if (!is_int($item->getValue())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, int|string> $cases
     */
    private function writeEnum(string $namespace, string $enumClassName, string $enumType, array $cases, string $table): void
    {
        $namespacePath = $this->configuration->targetDirectory . '/' . $this->namingHelper->directoryNameForTable($table);
        $enumDirectory = GeneralUtility::getFileAbsFileName($namespacePath);
        if (!is_dir($enumDirectory)) {
            GeneralUtility::mkdir_deep($enumDirectory);
        }

        $enumNamespace = new PhpNamespace($namespace);
        $enum = $enumNamespace->addEnum($enumClassName);
        $enum->setType($enumType);
        foreach ($cases as $caseName => $backingValue) {
            $enum->addCase($caseName, $enumType === 'int' ? (int)$backingValue : (string)$backingValue);
        }

        $file = new PhpFile();
        $file->addNamespace($enumNamespace);
        file_put_contents($enumDirectory . '/' . $enumClassName . '.php', $file);
    }

    /**
     * @param array<string, int|string> $cases
     */
    private function enumType(array $cases): string
    {
        foreach ($cases as $backingValue) {
            if (!is_int($backingValue)) {
                return 'string';
            }
        }

        return 'int';
    }

    private function enumClassName(string $table, ?string $type, TcaSchema $subSchema, FieldTypeInterface $field): string
    {
        if ($type !== null && !$this->hasTypeSpecificOverride($subSchema, $field)) {
            return $this->namingHelper->directoryNameForTable($table) . '_' . $this->identifierSegment($field->getName(), 'Field');
        }

        return $this->namingHelper->classNameForType($table, $type) . '_' . $this->identifierSegment($field->getName(), 'Field');
    }

    private function hasTypeSpecificOverride(TcaSchema $subSchema, FieldTypeInterface $field): bool
    {
        $columnsOverrides = $subSchema->getRawConfiguration()['columnsOverrides'] ?? [];
        return is_array($columnsOverrides) && array_key_exists($field->getName(), $columnsOverrides);
    }

    private function caseName(StaticSelectFieldType $field, string $label, int|string $value, bool $isIntBackedEnum): string
    {
        if ($isIntBackedEnum) {
            $resolvedLabel = $this->resolveLabel($label);
            $caseName = $resolvedLabel === null ? '' : $this->identifierSegment($resolvedLabel, '', true);
            if ($caseName !== '') {
                return $caseName;
            }
        }

        if (in_array($value, ['', 0], true) && $this->hasEmptyDefaultValue($field)) {
            return 'Default';
        }

        $caseName = $this->identifierSegment((string)$value, '', true);
        if ($caseName !== '') {
            return $caseName;
        }

        return 'Empty';
    }

    private function hasEmptyDefaultValue(StaticSelectFieldType $field): bool
    {
        return !$field->hasDefaultValue() || in_array($field->getDefaultValue(), [0, ''], true);
    }

    private function resolveLabel(string $label): ?string
    {
        if (str_starts_with($label, 'LLL:')) {
            $translatedLabel = LocalizationUtility::translate($label);
            return $translatedLabel !== null && !str_starts_with($translatedLabel, 'LLL:')
                ? $translatedLabel
                : null;
        }

        return $label;
    }

    private function identifierSegment(string $value, string $fallback, bool $suffixKeywords = false): string
    {
        $identifier = preg_replace('/[^a-zA-Z0-9]+/', ' ', $value) ?? '';
        $identifier = str_replace(' ', '', ucwords(trim($identifier)));
        if ($identifier === '') {
            return $fallback;
        }
        if (is_numeric($identifier[0])) {
            $identifier = 'Value' . $identifier;
        }
        if (!Helpers::isIdentifier($identifier)) {
            return $fallback;
        }
        if (isset(Helpers::Keywords[strtolower($identifier)])) {
            return $suffixKeywords ? $identifier . 'Value' : $fallback;
        }

        return $identifier;
    }
}
