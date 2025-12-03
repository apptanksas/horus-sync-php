<?php

namespace AppTank\Horus\Illuminate\Migration;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EntityMigrator
{
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
                $parameters = array_merge($entityClass::baseParameters(), $entityClass::parameters());
                foreach ($parameters as $parameter) {
                    if (($connectionName != null && Schema::connection($connectionName)->hasColumn($tableName, $parameter->name)) ||
                        Schema::hasColumn($tableName, $parameter->name)) {
                        continue;
                    }
                    $this->createColumn($table, $parameter);
                }
                // Add indexes
                if (!empty($columnIndexes)) {
                    $table->index($columnIndexes);
                }
            };

            $createTableConstraintsTable = function () use ($entityClass, $tableName, $connectionName) {
                $parameters = array_merge($entityClass::baseParameters(), $entityClass::parameters());
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
    private function createColumn(Blueprint $table, SyncParameter $parameter): void
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

        if ($driver !== 'pgsql' || $parameter->type !== SyncParameterType::CUSTOM) {
            return;
        }

        // ONLY FOR POSTGRESQL
        // Add a custom constraint to the table
        DB::connection($connectionName)->statement("ALTER TABLE $tableName
            ADD CONSTRAINT {$parameter->name}_type_custom CHECK (
                {$parameter->name} ~* '{$parameter->regex}'
            )");
    }
}