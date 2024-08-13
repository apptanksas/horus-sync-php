<?php

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/**
 * Create migration schema for all entities synchronizable
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->down();

        $container = HorusContainer::getInstance();

        /**
         * @var EntitySynchronizable $entityClass
         */
        foreach ($container->getEntities() as $entityClass) {
            $tableName = $entityClass::getTableName();

            $callbackCreateTable = function (Blueprint $table) use ($entityClass, $tableName) {
                $parameters = array_merge(EntitySynchronizable::baseParameters(), $entityClass::parameters());
                foreach ($parameters as $parameter) {
                    if (Schema::hasColumn($tableName, $parameter->name)) {
                        continue;
                    }
                    $this->createColumn($table, $parameter);
                }
            };

            // if connection name is null, use default connection
            if (is_null($container->getConnectionName())) {
                Schema::create($tableName, $callbackCreateTable);
                continue;
            }

            Schema::connection($container->getConnectionName())->create($tableName, $callbackCreateTable);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $container = HorusContainer::getInstance();

        /**
         * @var EntitySynchronizable $entityClass
         */
        foreach ($container->getEntities() as $entityClass) {
            $tableName = $entityClass::getTableName();

            if (is_null($container->getConnectionName())) {
                Schema::dropIfExists($tableName);
                continue;
            }
            Schema::connection($container->getConnectionName())->dropIfExists($tableName);
        }
    }

    private function createColumn(Blueprint $table, SyncParameter $parameter): void
    {

        if ($parameter->name == EntitySynchronizable::ATTR_SYNC_DELETED_AT) {
            $table->softDeletes(EntitySynchronizable::ATTR_SYNC_DELETED_AT);
            return;
        }

        if ($parameter->name == EntitySynchronizable::ATTR_SYNC_HASH) {
            $table->string($parameter->name, Hasher::getHashLength());
            return;
        }

        $builder = match ($parameter->type) {
            SyncParameterType::PRIMARY_KEY_INTEGER => $table->id($parameter->name),
            SyncParameterType::PRIMARY_KEY_UUID => $table->uuid($parameter->name)->unique(),
            SyncParameterType::PRIMARY_KEY_STRING => $table->string($parameter->name)->unique(),
            SyncParameterType::INT => $table->integer($parameter->name),
            SyncParameterType::FLOAT => $table->float($parameter->name),
            SyncParameterType::BOOLEAN => $table->boolean($parameter->name),
            SyncParameterType::STRING => $table->string($parameter->name, 255),
            SyncParameterType::JSON => $table->json($parameter->name),
            SyncParameterType::TEXT => $table->text($parameter->name),
            SyncParameterType::TIMESTAMP => $table->timestamp($parameter->name),
            SyncParameterType::ENUM => $table->enum($parameter->name, $parameter->options),
            SyncParameterType::UUID => $table->uuid($parameter->name),
            SyncParameterType::RELATION_ONE_TO_MANY => null,
        };

        if (!is_null($builder)) {
            $builder->nullable($parameter->isNullable);
        }
    }

};