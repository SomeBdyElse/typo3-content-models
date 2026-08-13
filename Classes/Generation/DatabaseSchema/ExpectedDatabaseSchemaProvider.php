<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\DatabaseSchema;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\ServerVersionProvider;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\MySQLPlatform;
use TYPO3\CMS\Core\Database\Platform\SQLitePlatform;
use TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ExpectedDatabaseSchemaProvider implements DatabaseSchemaProviderInterface
{
    /**
     * @var array<string, Table>|null
     */
    private ?array $tables = null;

    public function __construct(
        private readonly SqlReader $sqlReader,
        private readonly Parser $parser,
        private readonly DefaultTcaSchema $defaultTcaSchema,
    ) {
    }

    public function getColumn(string $table, string $field): ?Column
    {
        $schemaTable = $this->getTables()[$table] ?? null;
        if ($schemaTable === null || !$schemaTable->hasColumn($field)) {
            return null;
        }

        return $schemaTable->getColumn($field);
    }

    /**
     * Builds TYPO3's expected database schema without comparing it to an active
     * database connection.
     *
     * This intentionally mirrors the non-DB-building part of
     * TYPO3\CMS\Core\Database\Schema\SchemaMigrator::parseCreateTableStatements().
     *
     * @return array<string, Table>
     */
    private function getTables(): array
    {
        if ($this->tables !== null) {
            return $this->tables;
        }

        $statements = $this->sqlReader->getCreateTableStatementArray(
            $this->sqlReader->getTablesDefinitionString(),
        );

        $tables = $this->prepareTablesFromStatements($statements);
        $tables = $this->ensureTableDefinitionForAllTcaManagedTables($tables);
        $tables = $this->mergeTableDefinitions($tables);
        $tables = $this->enrichTables($tables);

        $this->tables = $tables;

        return $this->tables;
    }

    /**
     * Mirrors TYPO3\CMS\Core\Database\Schema\SchemaMigrator::prepareTablesFromStatements().
     *
     * @param string[] $statements
     * @return list<Table>
     */
    private function prepareTablesFromStatements(array $statements): array
    {
        $tables = [];
        foreach ($statements as $statement) {
            array_push($tables, ...$this->parser->parse($statement));
        }

        return $tables;
    }

    /**
     * Mirrors TYPO3\CMS\Core\Database\Schema\SchemaMigrator::ensureTableDefinitionForAllTCAManagedTables().
     *
     * @param list<Table> $tables
     * @return list<Table>
     */
    private function ensureTableDefinitionForAllTcaManagedTables(array $tables): array
    {
        $tableNamesFromExtTables = array_map(
            fn(Table $table): string => $this->trimIdentifierQuotes($table->getName()),
            $tables,
        );

        foreach (array_diff(array_keys($GLOBALS['TCA']), array_unique($tableNamesFromExtTables)) as $tableName) {
            array_push($tables, ...$this->parser->parse('CREATE TABLE ' . $tableName . '();'));
        }

        return $tables;
    }

    /**
     * Mirrors TYPO3\CMS\Core\Database\Schema\SchemaMigrator::mergeTableDefinitions().
     *
     * @param list<Table> $tables
     * @return array<string, Table>
     */
    private function mergeTableDefinitions(array $tables): array
    {
        $mergedTables = [];
        foreach ($tables as $table) {
            $tableName = $this->trimIdentifierQuotes($table->getName());
            if (!array_key_exists($tableName, $mergedTables)) {
                $mergedTables[$tableName] = $table;
                continue;
            }

            $currentTable = $mergedTables[$tableName];
            $mergedTables[$tableName] = new Table(
                $tableName,
                $this->mergeColumns(...array_values($currentTable->getColumns()), ...array_values($table->getColumns())),
                $this->mergeIndexes(...array_values($currentTable->getIndexes()), ...array_values($table->getIndexes())),
                [],
                array_merge($currentTable->getForeignKeys(), $table->getForeignKeys()),
                array_merge($currentTable->getOptions(), $table->getOptions()),
            );
        }

        return $mergedTables;
    }

    /**
     * Mirrors TYPO3\CMS\Core\Database\Schema\SchemaMigrator::mergeColumns().
     *
     * @return Column[]
     */
    private function mergeColumns(Column ...$columns): array
    {
        $mergedColumns = [];
        foreach ($columns as $column) {
            $mergedColumns[$column->getName()] = $column;
        }

        return array_values($mergedColumns);
    }

    /**
     * Mirrors TYPO3\CMS\Core\Database\Schema\SchemaMigrator::mergeIndexes().
     */
    private function mergeIndexes(\Doctrine\DBAL\Schema\Index ...$indexes): array
    {
        $mergedIndexes = [];
        foreach ($indexes as $index) {
            $mergedIndexes[$index->getName()] = $index;
        }

        return array_values($mergedIndexes);
    }

    private function trimIdentifierQuotes(string $identifier): string
    {
        // Mirrors TYPO3\CMS\Core\Database\Schema\SchemaMigrator::trimIdentifierQuotes().
        return str_replace(['`', '"', '[', ']'], '', $identifier);
    }

    /**
     * @param array<string, Table> $tables
     * @return array<string, Table>
     */
    private function enrichTables(array $tables): array
    {
        try {
            return $this->defaultTcaSchema->enrich($tables);
        } catch (\Throwable) {
            // TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema::enrichSingleTableFieldsFromTcaColumns()
            // currently resolves ConnectionPool via GeneralUtility::makeInstance() to inspect the
            // table platform for decimal number fields. During build-time generation there may be no
            // active database connection. The error may have to do with that.
            // Fake a minimal platform-only ConnectionPool.
            $this->queueBuildTimeConnectionPools();
            return $this->defaultTcaSchema->enrich($tables);
        }
    }

    /**
     * Queues instances for TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema::enrichSingleTableFieldsFromTcaColumns(),
     * which calls GeneralUtility::makeInstance(ConnectionPool::class) once per TCA table with columns.
     */
    private function queueBuildTimeConnectionPools(): void
    {
        $connectionPool = new class extends ConnectionPool {
            public function getConnectionForTable(string $tableName): Connection
            {
                return new class (
                    [],
                    new class implements Driver {
                        public function connect(array $params): DriverConnection
                        {
                            throw new \LogicException('The build-time schema connection must not connect to a database.');
                        }

                        public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
                        {
                            throw new \LogicException('The build-time schema connection provides its platform directly.');
                        }

                        public function getExceptionConverter(): ExceptionConverter
                        {
                            throw new \LogicException('The build-time schema connection must not convert driver exceptions.');
                        }
                    },
                    null,
                    $this->isTableConfiguredForSqlite($tableName),
                ) extends Connection {
                    public function __construct(
                        #[\SensitiveParameter]
                        array $params,
                        Driver $driver,
                        ?Configuration $config = null,
                        private readonly bool $sqlite = false,
                    ) {
                    }

                    public function getDatabasePlatform(): AbstractPlatform
                    {
                        return $this->sqlite ? new SQLitePlatform() : new MySQLPlatform();
                    }
                };
            }

            private function isTableConfiguredForSqlite(string $tableName): bool
            {
                // Mirrors the table-to-connection lookup needed before
                // TYPO3\CMS\Core\Database\ConnectionPool::getConnectionForTable() would instantiate a real connection.
                $connectionName = (string)($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$tableName] ?? self::DEFAULT_CONNECTION_NAME);
                $connection = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName] ?? [];

                $driver = strtolower((string)($connection['driver'] ?? ''));
                $driverClass = strtolower((string)($connection['driverClass'] ?? ''));
                $url = strtolower((string)($connection['url'] ?? ''));

                return in_array($driver, ['pdo_sqlite', 'sqlite3'], true)
                    || str_contains($driverClass, 'sqlite')
                    || str_starts_with($url, 'sqlite:')
                    || str_starts_with($url, 'pdo-sqlite:');
            }
        };
        $tablesWithColumnsCount = count(array_filter(
            $GLOBALS['TCA'],
            static fn(array $tableDefinition): bool => isset($tableDefinition['columns']) && is_array($tableDefinition['columns']),
        ));

        for ($i = 0; $i < $tablesWithColumnsCount; $i++) {
            GeneralUtility::addInstance(ConnectionPool::class, $connectionPool);
        }
    }
}
