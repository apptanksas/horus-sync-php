<?php

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $container = HorusContainer::getInstance();

        /**
         * @var EntitySynchronizable $entityClass
         */
        foreach ($container->getEntities() as $entityClass) {
            $tableName = $entityClass::getTableName();
            Schema::create($tableName, function (Blueprint $table) use ($entityClass, $tableName) {
                $parameters = $entityClass::parameters();
                foreach ($parameters as $parameter) {
                    if (Schema::hasColumn($tableName, $parameter->name)) {
                        continue;
                    }
                    $this->createColumn($table, $parameter);
                }
            });
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
            Schema::dropIfExists($tableName);
        }
    }

    private function createColumn(Blueprint $table, SyncParameter $parameter): void
    {
        match ($parameter->type) {
            SyncParameterType::PRIMARY_KEY_INTEGER => $table->id($parameter->name),
            SyncParameterType::PRIMARY_KEY_UUID => $table->uuid($parameter->name),
            SyncParameterType::PRIMARY_KEY_STRING => $table->string($parameter->name)->unique(),
            SyncParameterType::INT => $table->integer($parameter->name),
            SyncParameterType::FLOAT => $table->float($parameter->name),
            SyncParameterType::BOOLEAN => $table->boolean($parameter->name),
            SyncParameterType::STRING => $table->string($parameter->name),
            SyncParameterType::JSON => $table->json($parameter->name),
            SyncParameterType::TEXT => $table->text($parameter->name),
            SyncParameterType::TIMESTAMP => $table->timestamp($parameter->name),
            SyncParameterType::RELATION_ONE_TO_MANY => null,
        };
    }

};