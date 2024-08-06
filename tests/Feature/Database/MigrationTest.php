<?php

namespace Tests\Feature\Database;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ChildFakeEntity;
use Tests\_Stubs\ParentFakeEntity;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $initializeContainer = false;

    function setUp(): void
    {
        HorusContainer::initialize([
            ParentFakeEntity::class => [
                ChildFakeEntity::class
            ]
        ]);

        parent::setUp();
    }

    function testMigrations()
    {
        $this->assertDatabaseEmpty(ParentFakeEntity::getTableName());
        $this->assertDatabaseEmpty(ChildFakeEntity::getTableName());
        $this->assertDatabaseEmpty(SyncQueueActionModel::TABLE_NAME);
    }

    function testRollbackMigrationParentFake()
    {
        $this->artisan('migrate:rollback');
        $this->expectException(QueryException::class);
        $this->assertDatabaseEmpty(ParentFakeEntity::getTableName());
    }

    function testRollbackMigrationChildFake()
    {
        $this->artisan('migrate:rollback');
        $this->expectException(QueryException::class);
        $this->assertDatabaseEmpty(ChildFakeEntity::getTableName());
    }

    function testRollbackMigrationSyncQueueAction()
    {
        $this->artisan('migrate:rollback');
        $this->expectException(QueryException::class);
        $this->assertDatabaseEmpty(SyncQueueActionModel::TABLE_NAME);
    }
}