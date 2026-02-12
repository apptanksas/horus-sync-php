<?php

namespace AppTank\Horus\Illuminate\Migration;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EntityMigrator
{
    /**
     * Store parameters that need spatial indexes to be created after table creation.
     * @var array
     */
    private array $pendingSpatialIndexes = [];

    /**
     * Track if PostGIS extension has been enabled for PostgreSQL.
     * @var bool
     */
    private bool $postgisEnabled = false;

    /**
     * Migrate the given entity classes to the database.
     *
     * @param array $entityClasses
     * @param string|null $connectionName
     * @return void
     */
    function migrate(array $entityClasses, string|null $connectionName = null)
    {
        /*
         * @var IEntitySynchronizable $entityClass
         */
        foreach ($entityClasses as $entityClass) {

            $tableName = $entityClass::getTableName();
            $columnIndexes = $entityClass::getColumnIndexes();

            $callbackCreateTable = function (Blueprint $table) use ($entityClass, $tableName, $columnIndexes, $connectionName) {
                $parameters = array_merge($entityClass::baseParameters($this->getEntityBaseVersion($entityClass::parameters())), $entityClass::parameters());
                foreach ($parameters as $parameter) {
                    if (($connectionName != null && Schema::connection($connectionName)->hasColumn($tableName, $parameter->name)) ||
                        Schema::hasColumn($tableName, $parameter->name)) {
                        continue;
                    }
                    $this->createColumn($table, $parameter, $tableName, $connectionName);
                }
                // Add indexes
                if (!empty($columnIndexes)) {
                    $table->index($columnIndexes);
                }
            };

            $createTableConstraintsTable = function () use ($entityClass, $tableName, $connectionName) {
                $parameters = array_merge($entityClass::baseParameters($this->getEntityBaseVersion($entityClass::parameters())), $entityClass::parameters());
                foreach ($parameters as $parameter) {
                    if (($connectionName != null && Schema::connection($connectionName)->hasColumn($tableName, $parameter->name)) ||
                        Schema::hasColumn($tableName, $parameter->name)) {
                        continue;
                    }
                    $this->applyCustomConstraints($connectionName, $tableName, $parameter);
                }
            };

            // if connection name is null, use default connection
            if (is_null($connectionName)) {
                // Validate if the table already exists
                if (!Schema::hasTable($tableName)) {
                    Schema::create($tableName, $callbackCreateTable);
                    $createTableConstraintsTable->__invoke();
                }
                continue;
            }

            // Validate if the table already exists
            if (Schema::connection($connectionName)->hasTable($tableName)) {
                continue;
            }

            Schema::connection($connectionName)->create($tableName, $callbackCreateTable);
            $createTableConstraintsTable->__invoke();
        }

    }


    /**
     * Rollback the given entity classes from the database.
     *
     * @param array $entityClasses
     * @param string|null $connectionName
     * @return void
     */
    function rollback(array $entityClasses, string|null $connectionName = null)
    {
        Schema::connection($connectionName)->disableForeignKeyConstraints();

        /**
         * @var WritableEntitySynchronizable $entityClass
         */
        foreach (array_reverse($entityClasses) as $entityClass) {

            $tableName = $entityClass::getTableName();
            if (is_null($connectionName)) {
                Schema::dropIfExists($tableName);
                continue;
            }
            Schema::connection($connectionName)->dropIfExists($tableName);
        }

        Schema::connection($connectionName)->enableForeignKeyConstraints();
    }


    /**
     * Create a column in the given table based on the SyncParameter.
     *
     * @param Blueprint $table
     * @param SyncParameter $parameter
     * @return void
     */
    private function createColumn(Blueprint $table, SyncParameter $parameter, string $tableName, ?string $connectionName): void
    {

        if ($parameter->name == EntitySynchronizable::ATTR_SYNC_DELETED_AT) {
            $table->softDeletes(EntitySynchronizable::ATTR_SYNC_DELETED_AT);
            return;
        }

        if ($parameter->name == WritableEntitySynchronizable::ATTR_SYNC_HASH) {
            $table->string($parameter->name, Hasher::getHashLength());
            return;
        }

        // Validate if a foreign key is linked
        if ($parameter->linkedEntity !== null) {

            /**
             * @var EntitySynchronizable $entityClass
             */
            $entityClass = Horus::getInstance()->getEntityMapper()->getEntityClass($parameter->linkedEntity);
            $tableRelatedName = $entityClass::getTableName();

            $columnReference = match ($parameter->type) {
                SyncParameterType::STRING => $table->foreign($parameter->name),
                SyncParameterType::INT => $table->foreignId($parameter->name),
                SyncParameterType::UUID => $table->foreignUuid($parameter->name),
                default => null,
            };

            $columnReference = $columnReference->nullable($parameter->isNullable);
            $columnReference = $columnReference->references(EntitySynchronizable::ATTR_ID)->on($tableRelatedName);

            if ($parameter->deleteOnCascade) {
                $columnReference->cascadeOnDelete();
            }

            return;
        }

        $builder = match ($parameter->type) {
            SyncParameterType::PRIMARY_KEY_INTEGER => $table->id($parameter->name),
            SyncParameterType::PRIMARY_KEY_UUID => $table->uuid($parameter->name)->unique(),
            SyncParameterType::PRIMARY_KEY_STRING => $table->string($parameter->name)->unique(),
            SyncParameterType::INT => $table->integer($parameter->name),
            SyncParameterType::FLOAT => $table->float($parameter->name),
            SyncParameterType::BOOLEAN => $table->boolean($parameter->name),
            SyncParameterType::STRING, SyncParameterType::CUSTOM => $table->string($parameter->name, 255),
            SyncParameterType::JSON => $table->json($parameter->name),
            SyncParameterType::TEXT => $table->text($parameter->name),
            SyncParameterType::TIMESTAMP => $table->timestamp($parameter->name),
            SyncParameterType::ENUM => $table->enum($parameter->name, $parameter->options),
            SyncParameterType::UUID => $table->uuid($parameter->name),
            SyncParameterType::REFERENCE_FILE => $table->string($parameter->name),
            SyncParameterType::COORDINATES => $this->createCoordinatesColumn($table, $parameter),
            SyncParameterType::RELATION_ONE_OF_MANY, SyncParameterType::RELATION_ONE_OF_ONE => null,
            default => throw new \InvalidArgumentException("Parameter type not referenced: {$parameter->type}"),
        };

        if (!is_null($builder)) {
            $builder->nullable($parameter->isNullable);
        }

        if ($parameter->withIndex) {
            $builder->index();
        }
    }

    /**
     * Apply custom constraints to the table based on the SyncParameter.
     *
     * @param string|null $connectionName
     * @param string $tableName
     * @param SyncParameter $parameter
     * @return void
     */
    private function applyCustomConstraints(?string $connectionName, string $tableName, SyncParameter $parameter): void
    {
        $driver = (is_null($connectionName)) ? Schema::getConnection()->getDriverName() : Schema::connection($connectionName)->getConnection()->getDriverName();

        // Apply custom regex constraint for CUSTOM type in PostgreSQL
        if ($driver === 'pgsql' && $parameter->type === SyncParameterType::CUSTOM) {
            DB::connection($connectionName)->statement("ALTER TABLE $tableName
                ADD CONSTRAINT {$parameter->name}_type_custom CHECK (
                    {$parameter->name} ~* '{$parameter->regex}'
                )");
        }

        // Apply spatial indexes for COORDINATES type
        $indexKey = $tableName . '.' . $parameter->name;
        if ($parameter->type === SyncParameterType::COORDINATES && isset($this->pendingSpatialIndexes[$indexKey])) {
            $this->createSpatialIndex($connectionName, $tableName, $parameter->name, $driver);
            unset($this->pendingSpatialIndexes[$indexKey]);
        }
    }

    /**
     * Create a spatial index for coordinates column.
     *
     * @param string|null $connectionName
     * @param string $tableName
     * @param string $columnName
     * @param string $driver
     * @return void
     */
    private function createSpatialIndex(?string $connectionName, string $tableName, string $columnName, string $driver): void
    {
        $connection = $connectionName ? DB::connection($connectionName) : DB::connection();

        try {
            switch ($driver) {
                case 'pgsql':
                    // PostgreSQL: Ensure PostGIS extension is enabled before creating GIST index
                    $this->ensurePostGISExtension($connectionName);

                    // PostgreSQL: Use GIST index for POINT type
                    $connection->statement("CREATE INDEX IF NOT EXISTS idx_{$tableName}_{$columnName} ON {$tableName} USING GIST ({$columnName})");
                    break;

                case 'mysql':
                    // MySQL: Use SPATIAL index
                    $connection->statement("CREATE SPATIAL INDEX idx_{$tableName}_{$columnName} ON {$tableName}({$columnName})");
                    break;

                case 'sqlsrv':
                    // SQL Server: Use spatial index on GEOGRAPHY type
                    $connection->statement("CREATE SPATIAL INDEX idx_{$tableName}_{$columnName} ON {$tableName}({$columnName}) WITH (BOUNDING_BOX = (XMIN = -180, YMIN = -90, XMAX = 180, YMAX = 90))");
                    break;

                default:
                    // No spatial index support for other drivers
                    break;
            }
        } catch (\Exception $e) {
            // Silently fail if spatial index creation fails
            // This can happen if the extension is not installed or insufficient permissions
        }
    }


    /**
     * Get the base version from the entity parameters.
     *
     * @param array $entityParameters
     * @return int
     */
    private function getEntityBaseVersion(array $entityParameters): int
    {
        $baseVersion = PHP_INT_MAX;

        foreach ($entityParameters as $parameter) {
            if ($baseVersion > $parameter->version) {
                $baseVersion = $parameter->version;
            }
        }

        return $baseVersion;
    }


    /**
     * Create a coordinates column based on the database driver.
     *
     * @param Blueprint $table
     * @param SyncParameter $parameter
     * @return ColumnDefinition|null
     */
    private function createCoordinatesColumn(Blueprint $table, SyncParameter $parameter): ?ColumnDefinition
    {
        $driver = Schema::getConnection()->getDriverName();
        $tableName = $table->getTable();

        switch ($driver) {
            case 'pgsql':
                // PostgreSQL: Ensure PostGIS extension is enabled
                $this->ensurePostGISExtension();

                // PostgreSQL: Use native POINT type
                // Using addColumn for better Blueprint integration
                $column = $table->addColumn('point', $parameter->name);
                if ($parameter->isNullable) {
                    $column->nullable();
                }

                // Spatial index will be added after table creation if needed
                if ($parameter->withIndex) {
                    // Store for later index creation in applyCustomConstraints
                    $this->pendingSpatialIndexes[$tableName . '.' . $parameter->name] = true;
                }
                return $column;

            case 'mysql':
                // MySQL: Use POINT type with spatial support
                $column = $table->addColumn('point', $parameter->name);
                if ($parameter->isNullable) {
                    $column->nullable();
                }

                // Spatial index will be added after table creation
                if ($parameter->withIndex) {
                    $this->pendingSpatialIndexes[$tableName . '.' . $parameter->name] = true;
                }
                return $column;

            case 'sqlite':
                // SQLite: No native spatial support, use TEXT to store WKT (Well-Known Text)
                // Format: "POINT(longitude latitude)"
                $column = $table->text($parameter->name);
                if ($parameter->isNullable) {
                    $column->nullable();
                }

                if ($parameter->withIndex) {
                    $column->index();
                }
                return $column;

            case 'sqlsrv':
                // SQL Server: Use GEOGRAPHY type
                // Need to use raw expression as Laravel doesn't have native support
                $column = $table->addColumn('geography', $parameter->name);
                if ($parameter->isNullable) {
                    $column->nullable();
                }

                if ($parameter->withIndex) {
                    $this->pendingSpatialIndexes[$tableName . '.' . $parameter->name] = true;
                }
                return $column;

            default:
                // Fallback: Use string to store coordinates as "lat,lon" or WKT format
                $column = $table->string($parameter->name, 100);
                if ($parameter->isNullable) {
                    $column->nullable();
                }
                if ($parameter->withIndex) {
                    $column->index();
                }
                return $column;
        }
    }

    /**
     * Ensure PostGIS extension is enabled for PostgreSQL.
     * This method is called before creating POINT columns or spatial indexes.
     *
     * @param string|null $connectionName
     * @return void
     */
    private function ensurePostGISExtension(?string $connectionName = null): void
    {
        // Only run once per migration instance
        if ($this->postgisEnabled) {
            return;
        }

        $connection = $connectionName ? DB::connection($connectionName) : DB::connection();

        try {
            // Check if we're using PostgreSQL
            if ($connection->getDriverName() !== 'pgsql') {
                return;
            }

            // Try to create the PostGIS extension if it doesn't exist
            // IF NOT EXISTS prevents errors if already installed
            $connection->statement('CREATE EXTENSION IF NOT EXISTS postgis');

            $this->postgisEnabled = true;
        } catch (\Exception $e) {
            Log::error("Failed to enable PostGIS extension: " . $e->getMessage());
        }
    }
}
