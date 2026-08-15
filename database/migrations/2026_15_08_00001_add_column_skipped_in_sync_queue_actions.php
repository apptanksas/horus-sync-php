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
            if (!Schema::connection($container->getConnectionName())->hasColumn(SyncQueueActionModel::TABLE_NAME, SyncQueueActionModel::ATTR_SKIPPED)) {
                $table->boolean(SyncQueueActionModel::ATTR_SKIPPED)->default(false)->after(SyncQueueActionModel::ATTR_BY_SYSTEM);
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
            if (Schema::connection($container->getConnectionName())->hasColumn(SyncQueueActionModel::TABLE_NAME, SyncQueueActionModel::ATTR_SKIPPED)) {
                $table->removeColumn(SyncQueueActionModel::ATTR_SKIPPED);
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
