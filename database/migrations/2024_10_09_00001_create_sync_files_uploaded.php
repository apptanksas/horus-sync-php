<?php

use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;
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

        $callbackCreateTable = function (Blueprint $table) use ($container) {
            $table->uuid(SyncFileUploadedModel::ATTR_ID)->primary();
            $table->string(SyncFileUploadedModel::ATTR_MIME_TYPE, 255);
            $table->string(SyncFileUploadedModel::ATTR_PATH, 255);
            $table->string(SyncFileUploadedModel::ATTR_PUBLIC_URL, 255);

            // If uses uses UUID
            if ($container->isUsesUUID()) {
                $table->uuid(SyncFileUploadedModel::FK_OWNER_ID);
            } else {
                $table->unsignedBigInteger(SyncFileUploadedModel::FK_OWNER_ID);
            }

            $table->timestamps();
        };

        // if connection name is null, use default connection
        if (is_null($container->getConnectionName())) {
            Schema::create(SyncFileUploadedModel::TABLE_NAME, $callbackCreateTable);
            return;
        }

        Schema::connection($container->getConnectionName())->create(SyncFileUploadedModel::TABLE_NAME, $callbackCreateTable);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $container = Horus::getInstance();

        if (is_null($container->getConnectionName())) {
            Schema::dropIfExists(SyncFileUploadedModel::TABLE_NAME);
            return;
        }

        Schema::connection($container->getConnectionName())->dropIfExists(SyncFileUploadedModel::TABLE_NAME);
    }

};