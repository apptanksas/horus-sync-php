<?php

use AppTank\Horus\Horus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use AppTank\Horus\Core\SyncAction;
use Illuminate\Support\Str;

return new class extends Migration {
    public $withinTransaction = false;

    public function up(): void
    {
        $table = SyncQueueActionModel::TABLE_NAME;
        $column = SyncQueueActionModel::ATTR_ACTION;

        $enumValues = [
            SyncAction::INSERT->value,
            SyncAction::UPDATE->value,
            SyncAction::DELETE->value,
            SyncAction::UPDELETE->value,
        ];

        $connectionName = Horus::getInstance()->getConnectionName();
        $conn = DB::connection($connectionName);
        $driver = $conn->getDriverName();

        $schemaName = null;
        $bareTable = $table;

        if (strpos($table, '.') !== false) {
            [$schemaName, $bareTable] = explode('.', $table, 2);
        }

        // ---------------------------------------------
        // MySQL
        // ---------------------------------------------

        if ($driver === 'mysql') {
            $dbName = $conn->getDatabaseName();

            $col = $conn->selectOne("
                SELECT IS_NULLABLE, COLUMN_DEFAULT, CHARACTER_SET_NAME, COLLATION_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            ", [$dbName, $bareTable, $column]);

            $enumList = implode("','", array_map(fn($v) => str_replace("'", "''", $v), $enumValues));
            $enumSql = "ENUM('" . $enumList . "')";

            $nullable = ($col && $col->IS_NULLABLE === 'YES') ? 'NULL' : 'NOT NULL';
            $default = ($col && $col->COLUMN_DEFAULT !== null) ? "DEFAULT " . $col->COLUMN_DEFAULT : '';
            $charsetCollate = '';
            if ($col && $col->CHARACTER_SET_NAME) {
                $charsetCollate = " CHARACTER SET {$col->CHARACTER_SET_NAME}";
                if ($col->COLLATION_NAME) {
                    $charsetCollate .= " COLLATE {$col->COLLATION_NAME}";
                }
            }

            $sql = "ALTER TABLE `{$bareTable}` MODIFY `{$column}` {$enumSql}{$charsetCollate} {$nullable} {$default}";
            $conn->statement($sql);

            // ---------------------------------------------
            // PostgreSQL
            // ---------------------------------------------

        } elseif ($driver === 'pgsql') {

            if (is_null($schemaName)) {
                $srow = $conn->selectOne('SELECT current_schema() AS schema_name');
                $schemaName = $srow->schema_name ?? 'public';
            }

            // First, drop the check constraint if it exists
            $this->dropCheckConstraint($conn, $schemaName, $bareTable, $column);

            $typeRow = $conn->selectOne("
                SELECT pg_type.typname AS enum_type
                FROM pg_attribute a
                JOIN pg_class c ON a.attrelid = c.oid
                JOIN pg_type ON a.atttypid = pg_type.oid
                JOIN pg_namespace n ON c.relnamespace = n.oid
                WHERE c.relname = ? AND a.attname = ? AND pg_type.typtype = 'e' AND n.nspname = ?
                LIMIT 1
            ", [$bareTable, $column, $schemaName]);

            if ($typeRow && isset($typeRow->enum_type)) {
                $typeName = $typeRow->enum_type;

                try {
                    foreach ($enumValues as $val) {
                        $safe = str_replace("'", "''", $val);
                        $conn->statement("ALTER TYPE \"{$schemaName}\".\"{$typeName}\" ADD VALUE IF NOT EXISTS '{$safe}'");
                    }
                } catch (\Throwable $e) {
                    $this->recreatePgEnumType($conn, $schemaName, $bareTable, $column, $typeName, $enumValues);
                }
            } else {
                $tentativeType = "{$schemaName}_{$bareTable}_{$column}_enum";
                $this->recreatePgEnumType($conn, $schemaName, $bareTable, $column, null, $enumValues, $tentativeType);
            }

            // Add new check constraint with all values
            $this->addCheckConstraint($conn, $schemaName, $bareTable, $column, $enumValues);
        }
    }

    private function dropCheckConstraint($conn, string $schemaName, string $table, string $column): void
    {
        $constraintName = "{$table}_{$column}_check";
        $conn->statement("ALTER TABLE \"{$schemaName}\".\"{$table}\" DROP CONSTRAINT IF EXISTS \"{$constraintName}\"");
    }

    private function addCheckConstraint($conn, string $schemaName, string $table, string $column, array $values): void
    {
        $constraintName = "{$table}_{$column}_check";
        $valuesList = implode("','", array_map(fn($v) => str_replace("'", "''", $v), $values));
        $conn->statement("ALTER TABLE \"{$schemaName}\".\"{$table}\" ADD CONSTRAINT \"{$constraintName}\" CHECK (\"{$column}\"::text = ANY (ARRAY['{$valuesList}']))");
    }

    /**
     * Recreate enum type in Postgres (works on the provided connection).
     *
     * @param \Illuminate\Database\Connection $conn
     * @param string $schemaName
     * @param string $table
     * @param string $column
     * @param string|null $oldTypeName
     * @param array $newValues
     * @param string|null $forceTypeName
     * @return void
     */
    private function recreatePgEnumType($conn, string $schemaName, string $table, string $column, ?string $oldTypeName, array $newValues, ?string $forceTypeName = null): void
    {
        $tmpType = 'tmp_enum_' . Str::random(8);
        $vals = implode("','", array_map(fn($v) => str_replace("'", "''", $v), $newValues));

        // Create temporary type
        $conn->statement("CREATE TYPE \"{$schemaName}\".\"{$tmpType}\" AS ENUM('{$vals}')");

        $qualifiedTable = "\"{$schemaName}\".\"{$table}\"";
        $conn->statement("ALTER TABLE {$qualifiedTable} ALTER COLUMN \"{$column}\" TYPE \"{$schemaName}\".\"{$tmpType}\" USING {$column}::text::\"{$schemaName}\".\"{$tmpType}\"");

        // Determine final type name
        $finalTypeName = $oldTypeName ?: $forceTypeName;

        if ($finalTypeName) {
            // Drop existing type if exists (now safe because column uses temporary type)
            $conn->statement("DROP TYPE IF EXISTS \"{$schemaName}\".\"{$finalTypeName}\"");
            // Rename temporary type to final name
            $conn->statement("ALTER TYPE \"{$schemaName}\".\"{$tmpType}\" RENAME TO \"{$finalTypeName}\"");
        }
        // If no final name, leave temporary type as is
    }

    public function down(): void
    {
        $table = SyncQueueActionModel::TABLE_NAME;
        $column = SyncQueueActionModel::ATTR_ACTION;
        $enumValues = [SyncAction::INSERT->value, SyncAction::UPDATE->value, SyncAction::DELETE->value];

        $connectionName = Horus::getInstance()->getConnectionName();
        $conn = DB::connection($connectionName);
        $driver = $conn->getDriverName();

        $schemaName = null;
        $bareTable = $table;

        if (strpos($table, '.') !== false) {
            [$schemaName, $bareTable] = explode('.', $table, 2);
        }

        if ($driver === 'mysql') {
            $enumList = "'" . implode("','", $enumValues) . "'";
            $conn->statement("ALTER TABLE `{$bareTable}` MODIFY `{$column}` ENUM($enumList)");
        } elseif ($driver === 'pgsql') {
            if (is_null($schemaName)) {
                $srow = $conn->selectOne('SELECT current_schema() AS schema_name');
                $schemaName = $srow->schema_name ?? 'public';
            }

            // Drop current check constraint
            $this->dropCheckConstraint($conn, $schemaName, $bareTable, $column);

            $typeRow = $conn->selectOne("
                SELECT pg_type.typname AS enum_type
                FROM pg_attribute a
                JOIN pg_class c ON a.attrelid = c.oid
                JOIN pg_type ON a.atttypid = pg_type.oid
                JOIN pg_namespace n ON c.relnamespace = n.oid
                WHERE c.relname = ? AND a.attname = ? AND pg_type.typtype = 'e' AND n.nspname = ?
                LIMIT 1
            ", [$bareTable, $column, $schemaName]);

            $currentTypeName = $typeRow ? $typeRow->enum_type : null;
            $this->recreatePgEnumType($conn, $schemaName, $bareTable, $column, $currentTypeName, $enumValues);

            // Add back original check constraint
            $this->addCheckConstraint($conn, $schemaName, $bareTable, $column, $enumValues);
        }
    }
};
