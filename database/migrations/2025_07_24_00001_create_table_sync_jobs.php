<?php

use AppTank\Horus\Core\SyncJobStatus;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\SyncJobModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create table to store synchronization jobs and handle their status
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
            $table->uuid(SyncJobModel::ATTR_ID)->primary();
            $table->enum(SyncJobModel::ATTR_STATUS, SyncJobStatus::getValues());
            $table->string(SyncJobModel::ATTR_CHECKPOINT)->nullable();
            $table->string(SyncJobModel::ATTR_DOWNLOAD_URL, 1000)->nullable();
            $table->timestamp(SyncJobModel::ATTR_RESULTED_AT)->nullable();

            // If user uses UUID
            if ($container->isUsesUUID()) {
                $table->uuid(SyncJobModel::FK_USER_ID);
            } else {
                $table->unsignedBigInteger(SyncJobModel::FK_USER_ID);
            }

            $table->timestamps();
        };

        // if connection name is null, use default connection
        if (is_null($container->getConnectionName())) {
            Schema::create(SyncJobModel::TABLE_NAME, $callbackCreateTable);
            return;
        }

        Schema::connection($container->getConnectionName())->create(SyncJobModel::TABLE_NAME, $callbackCreateTable);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $container = Horus::getInstance();

        if (is_null($container->getConnectionName())) {
            Schema::dropIfExists(SyncJobModel::TABLE_NAME);
            return;
        }

        Schema::connection($container->getConnectionName())->dropIfExists(SyncJobModel::TABLE_NAME);
    }

};
