<?php

namespace Tests\Feature\Database;

use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\LookupSynchronizable;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\_Stubs\AdjacentFakeEntity;
use Tests\_Stubs\ChildFakeEntity;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\LookupFakeEntity;
use Tests\_Stubs\ParentFakeEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    function setUp(): void
    {
        Horus::initialize([
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
        $this->assertDatabaseEmpty(LookupFakeEntity::getTableName());
        $this->assertDatabaseEmpty(SyncQueueActionModel::TABLE_NAME);

        $parentColumns = Schema::getColumns(ParentFakeEntity::getTableName());
        $childColumns = Schema::getColumns(ChildFakeEntity::getTableName());
        $lookupColumns = Schema::getColumns(LookupFakeEntity::getTableName());

        // Validate nullable columns
        foreach (ParentFakeEntity::parameters() as $parameter) {

            if ($parameter->type->isRelation()) {
                continue;
            }

            $columnNullable = array_merge([], array_filter($parentColumns, function ($column) use ($parameter) {
                return $column["name"] == $parameter->name;
            }))[0] ?? throw new \Exception("Column not[" . $parameter->name . "] found");
            $this->assertEquals($parameter->isNullable, $columnNullable["nullable"], $parameter->name);
        }

        foreach (ChildFakeEntity::parameters() as $parameter) {
            $columnNullable = array_merge([], array_filter($childColumns, function ($column) use ($parameter) {
                return $column["name"] == $parameter->name;
            }))[0] ?? throw new \Exception("Column not[" . $parameter->name . "] found");
            $this->assertEquals($parameter->isNullable, $columnNullable["nullable"], $parameter->name);
        }


        // Validate lookup migration
        $columnIdAttributes = array_merge([], array_filter($lookupColumns, fn($column) => $column["name"] == EntitySynchronizable::ATTR_ID))[0];
        $this->assertEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == EntitySynchronizable::ATTR_SYNC_HASH));
        $this->assertEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == EntitySynchronizable::ATTR_SYNC_OWNER_ID));
        $this->assertEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == EntitySynchronizable::ATTR_SYNC_UPDATED_AT));
        $this->assertEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == EntitySynchronizable::ATTR_SYNC_CREATED_AT));
        $this->assertNotEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == EntitySynchronizable::ATTR_ID));
        $this->assertNotEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == "name"));
        $this->assertTrue($columnIdAttributes["type"] == "integer");
        $this->assertTrue($columnIdAttributes["auto_increment"] == true);
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

    public function testCheckForeignKeys()
    {
        DB::statement('PRAGMA foreign_keys = ON');
        $foreignKeysChild = DB::select("PRAGMA foreign_key_list('".ChildFakeEntity::getTableName()."')");
        $foreignKeysAdjacent = DB::select("PRAGMA foreign_key_list('".AdjacentFakeEntity::getTableName()."')");

        $this->assertNotEmpty($foreignKeysChild);
        $this->assertNotEmpty($foreignKeysAdjacent);

        $this->assertEquals(ChildFakeEntity::FK_PARENT_ID, $foreignKeysChild[0]->from);
        $this->assertEquals(ParentFakeEntity::getTableName(), $foreignKeysChild[0]->table);
        $this->assertEquals('CASCADE', $foreignKeysChild[0]->on_delete);

        $this->assertEquals(AdjacentFakeEntity::FK_PARENT_ID, $foreignKeysAdjacent[0]->from);
        $this->assertEquals(ParentFakeEntity::getTableName(), $foreignKeysAdjacent[0]->table);
        $this->assertEquals('CASCADE', $foreignKeysAdjacent[0]->on_delete);
    }
}