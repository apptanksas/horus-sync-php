<?php

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
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

        $callbackCreateTable = function (Blueprint $table) use ($container) {
            $table->id();
            $table->enum(SyncQueueActionModel::ATTR_ACTION, SyncAction::getValues());
            $table->string(SyncQueueActionModel::ATTR_ENTITY, 255);
            $table->json(SyncQueueActionModel::ATTR_DATA);
            $table->timestamp(SyncQueueActionModel::ATTR_ACTIONED_AT);
            $table->timestamp(SyncQueueActionModel::ATTR_SYNCED_AT);

            // If uses uses UUID
            if ($container->isUsesUUID()) {
                $table->uuid(SyncQueueActionModel::FK_USER_ID);
                $table->uuid(SyncQueueActionModel::FK_OWNER_ID);
            } else {
                $table->unsignedBigInteger(SyncQueueActionModel::FK_USER_ID);
                $table->unsignedBigInteger(SyncQueueActionModel::FK_OWNER_ID);
            }
        };

        // if connection name is null, use default connection
        if (is_null($container->getConnectionName())) {
            Schema::create(SyncQueueActionModel::TABLE_NAME, $callbackCreateTable);
            return;
        }

        Schema::connection($container->getConnectionName())->create(SyncQueueActionModel::TABLE_NAME, $callbackCreateTable);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $container = HorusContainer::getInstance();

        if (is_null($container->getConnectionName())) {
            Schema::dropIfExists(SyncQueueActionModel::TABLE_NAME);
            return;
        }
        Schema::connection($container->getConnectionName())->dropIfExists(SyncQueueActionModel::TABLE_NAME);
    }

};