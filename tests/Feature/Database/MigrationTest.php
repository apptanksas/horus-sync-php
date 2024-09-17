<?php

namespace Tests\Feature\Database;

use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use AppTank\Horus\Illuminate\Database\ReadableEntitySynchronizable;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\_Stubs\AdjacentFakeWritableEntity;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ReadableFakeEntity;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    function setUp(): void
    {
        Horus::initialize([
            ParentFakeWritableEntity::class => [
                ChildFakeWritableEntity::class
            ]
        ]);

        parent::setUp();
    }

    function testMigrations()
    {
        $this->assertDatabaseEmpty(ParentFakeWritableEntity::getTableName());
        $this->assertDatabaseEmpty(ChildFakeWritableEntity::getTableName());
        $this->assertDatabaseEmpty(ReadableFakeEntity::getTableName());
        $this->assertDatabaseEmpty(SyncQueueActionModel::TABLE_NAME);

        $parentColumns = Schema::getColumns(ParentFakeWritableEntity::getTableName());
        $childColumns = Schema::getColumns(ChildFakeWritableEntity::getTableName());
        $lookupColumns = Schema::getColumns(ReadableFakeEntity::getTableName());

        // Validate nullable columns
        foreach (ParentFakeWritableEntity::parameters() as $parameter) {

            if ($parameter->type->isRelation()) {
                continue;
            }

            $columnNullable = array_merge([], array_filter($parentColumns, function ($column) use ($parameter) {
                return $column["name"] == $parameter->name;
            }))[0] ?? throw new \Exception("Column not[" . $parameter->name . "] found");
            $this->assertEquals($parameter->isNullable, $columnNullable["nullable"], $parameter->name);
        }

        foreach (ChildFakeWritableEntity::parameters() as $parameter) {
            $columnNullable = array_merge([], array_filter($childColumns, function ($column) use ($parameter) {
                return $column["name"] == $parameter->name;
            }))[0] ?? throw new \Exception("Column not[" . $parameter->name . "] found");
            $this->assertEquals($parameter->isNullable, $columnNullable["nullable"], $parameter->name);
        }


        // Validate lookup migration
        $columnIdAttributes = array_merge([], array_filter($lookupColumns, fn($column) => $column["name"] == WritableEntitySynchronizable::ATTR_ID))[0];
        $this->assertEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == WritableEntitySynchronizable::ATTR_SYNC_HASH));
        $this->assertEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID));
        $this->assertEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT));
        $this->assertEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == WritableEntitySynchronizable::ATTR_SYNC_CREATED_AT));
        $this->assertNotEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == WritableEntitySynchronizable::ATTR_ID));
        $this->assertNotEmpty(array_filter($lookupColumns, fn($column) => $column["name"] == "name"));
        $this->assertTrue($columnIdAttributes["type"] == "integer");
        $this->assertTrue($columnIdAttributes["auto_increment"] == true);
    }

    function testRollbackMigrationParentFake()
    {
        $this->artisan('migrate:rollback');
        $this->expectException(QueryException::class);
        $this->assertDatabaseEmpty(ParentFakeWritableEntity::getTableName());
    }

    function testRollbackMigrationChildFake()
    {
        $this->artisan('migrate:rollback');
        $this->expectException(QueryException::class);
        $this->assertDatabaseEmpty(ChildFakeWritableEntity::getTableName());
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
        $foreignKeysChild = DB::select("PRAGMA foreign_key_list('".ChildFakeWritableEntity::getTableName()."')");
        $foreignKeysAdjacent = DB::select("PRAGMA foreign_key_list('".AdjacentFakeWritableEntity::getTableName()."')");

        $this->assertNotEmpty($foreignKeysChild);
        $this->assertNotEmpty($foreignKeysAdjacent);

        $this->assertEquals(ChildFakeWritableEntity::FK_PARENT_ID, $foreignKeysChild[0]->from);
        $this->assertEquals(ParentFakeWritableEntity::getTableName(), $foreignKeysChild[0]->table);
        $this->assertEquals('CASCADE', $foreignKeysChild[0]->on_delete);

        $this->assertEquals(AdjacentFakeWritableEntity::FK_PARENT_ID, $foreignKeysAdjacent[0]->from);
        $this->assertEquals(ParentFakeWritableEntity::getTableName(), $foreignKeysAdjacent[0]->table);
        $this->assertEquals('CASCADE', $foreignKeysAdjacent[0]->on_delete);
    }
}