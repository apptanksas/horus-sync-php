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
            if (!Schema::connection($container->getConnectionName())->hasColumn(SyncQueueActionModel::TABLE_NAME, SyncQueueActionModel::ATTR_EVENT_ID)) {
                $table->uuid(SyncQueueActionModel::ATTR_EVENT_ID)->nullable()->after("id");
            }
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
            if (Schema::connection($container->getConnectionName())->hasColumn(SyncQueueActionModel::TABLE_NAME, SyncQueueActionModel::ATTR_EVENT_ID)) {
                $table->removeColumn(SyncQueueActionModel::ATTR_EVENT_ID);
            }
        };

        // if connection name is null, use default connection
        if (is_null($container->getConnectionName())) {
            Schema::table(SyncQueueActionModel::TABLE_NAME, $callbackTable);
            return;
        }

        Schema::connection($container->getConnectionName())->table(SyncQueueActionModel::TABLE_NAME, $callbackTable);
    }
};
