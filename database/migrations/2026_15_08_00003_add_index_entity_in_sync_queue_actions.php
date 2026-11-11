<?php

use AppTank\Horus\Horus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        $container = Horus::getInstance();

        $callbackTable = function (Blueprint $table) use ($container) {
            $table->index([
                SyncQueueActionModel::ATTR_ENTITY,
                SyncQueueActionModel::ATTR_ENTITY_ID,
                SyncQueueActionModel::ATTR_ACTION,
            ]);
        };

        // if connection name is null, use default connection
        if (is_null($container->getConnectionName())) {
            Schema::table(SyncQueueActionModel::TABLE_NAME, $callbackTable);
            return;
        }

        Schema::connection($container->getConnectionName())->table(SyncQueueActionModel::TABLE_NAME, $callbackTable);
    }

    public function down(): void
    {
        $container = Horus::getInstance();

        $callbackTable = function (Blueprint $table) use ($container) {
            $table->dropIndex([
                SyncQueueActionModel::ATTR_ENTITY,
                SyncQueueActionModel::ATTR_ENTITY_ID,
                SyncQueueActionModel::ATTR_ACTION,
            ]);
        };

        // if connection name is null, use default connection
        if (is_null($container->getConnectionName())) {
            Schema::table(SyncQueueActionModel::TABLE_NAME, $callbackTable);
            return;
        }

        Schema::connection($container->getConnectionName())->table(SyncQueueActionModel::TABLE_NAME, $callbackTable);
    }
};
