<?php

use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
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

        $container = Horus::getInstance();

        /**
         * @var IEntitySynchronizable $entityClass
         */
        foreach ($container->getEntities() as $entityClass) {
            $tableName = $entityClass::getTableName();

            $callbackCreateTable = function (Blueprint $table) use ($entityClass, $tableName) {
                $parameters = array_merge($entityClass::baseParameters(), $entityClass::parameters());
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
        $container = Horus::getInstance();

        Schema::connection($container->getConnectionName())->disableForeignKeyConstraints();

        /**
         * @var WritableEntitySynchronizable $entityClass
         */
        foreach ($container->getEntities() as $entityClass) {
            $tableName = $entityClass::getTableName();

            if (is_null($container->getConnectionName())) {
                Schema::dropIfExists($tableName);
                continue;
            }
            Schema::connection($container->getConnectionName())->dropIfExists($tableName);
        }

        Schema::connection($container->getConnectionName())->enableForeignKeyConstraints();
    }

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

            match ($parameter->type) {
                SyncParameterType::STRING => $table->foreign($parameter->name)->references(EntitySynchronizable::ATTR_ID)->on($tableRelatedName)->cascadeOnDelete(),
                SyncParameterType::INT => $table->foreignId($parameter->name)->references(EntitySynchronizable::ATTR_ID)->on($tableRelatedName)->cascadeOnDelete(),
                SyncParameterType::UUID => $table->foreignUuid($parameter->name)->references(EntitySynchronizable::ATTR_ID)->on($tableRelatedName)->cascadeOnDelete(),
                default => null,
            };
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
            SyncParameterType::RELATION_ONE_OF_MANY, SyncParameterType::RELATION_ONE_OF_ONE => null,
        };

        if (!is_null($builder)) {
            $builder->nullable($parameter->isNullable);
        }

    }

};