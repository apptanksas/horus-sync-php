<?php

use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Migration\EntityMigrator;
use Illuminate\Database\Migrations\Migration;


/**
 * Create migration schema for all entities synchronizable
 */
return new class extends Migration {

    private EntityMigrator $migrator;


    function __construct()
    {
        $this->migrator = new EntityMigrator();
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->down();

        $container = Horus::getInstance();
        $connectionName = $container->getConnectionName();

        $this->migrator->migrate($container->getEntities(), $connectionName);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $container = Horus::getInstance();
        $this->migrator->rollback($container->getEntities(), $container->getConnectionName());
    }

};