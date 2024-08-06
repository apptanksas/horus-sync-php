<?php

namespace Tests\Feature\Database;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ChildFakeIEntity;
use Tests\_Stubs\ParentFakeEntity;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    function setUp(): void
    {
        HorusContainer::initialize([
            ParentFakeEntity::class,
            ChildFakeIEntity::class
        ]);

        parent::setUp();
    }

    function testMigrations()
    {
        $this->assertDatabaseEmpty(ParentFakeEntity::getTableName());
        $this->assertDatabaseEmpty(ChildFakeIEntity::getTableName());
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
        $this->assertDatabaseEmpty(ChildFakeIEntity::getTableName());
    }

    function testRollbackMigrationSyncQueueAction()
    {
        $this->artisan('migrate:rollback');
        $this->expectException(QueryException::class);
        $this->assertDatabaseEmpty(SyncQueueActionModel::TABLE_NAME);
    }
}