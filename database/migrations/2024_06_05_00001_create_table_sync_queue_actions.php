<?php

use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Horus;
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

        $container = Horus::getInstance();

        $callbackCreateTable = function (Blueprint $table) use ($container) {
            $table->id();
            $table->enum(SyncQueueActionModel::ATTR_ACTION, SyncAction::getValues());
            $table->string(SyncQueueActionModel::ATTR_ENTITY, 255);
            $table->uuid(SyncQueueActionModel::ATTR_ENTITY_ID);

            $table->json(SyncQueueActionModel::ATTR_DATA);

            $table->timestamp(SyncQueueActionModel::ATTR_ACTIONED_AT)->index();
            $table->timestamp(SyncQueueActionModel::ATTR_SYNCED_AT)->index();
            $table->boolean(SyncQueueActionModel::ATTR_BY_SYSTEM)->default(false);

            // If uses uses UUID
            if ($container->isUsesUUID()) {
                $table->uuid(SyncQueueActionModel::FK_USER_ID)->index();
                $table->uuid(SyncQueueActionModel::FK_OWNER_ID)->index();
            } else {
                $table->unsignedBigInteger(SyncQueueActionModel::FK_USER_ID);
                $table->unsignedBigInteger(SyncQueueActionModel::FK_OWNER_ID)->index();
            }

            $table->index([
                SyncQueueActionModel::FK_OWNER_ID,
                SyncQueueActionModel::ATTR_ACTIONED_AT,
                SyncQueueActionModel::ATTR_SYNCED_AT,
                SyncQueueActionModel::ATTR_ACTION
            ]);
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
        $container = Horus::getInstance();

        if (is_null($container->getConnectionName())) {
            Schema::dropIfExists(SyncQueueActionModel::TABLE_NAME);
            return;
        }
        Schema::connection($container->getConnectionName())->dropIfExists(SyncQueueActionModel::TABLE_NAME);
    }

};