<?php

namespace Tests\Feature\Database;

use AppTank\Horus\HorusContainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ChildFakeIEntity;
use Tests\_Stubs\ParentFakeIEntity;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    function setUp(): void
    {
        HorusContainer::initialize([
            ParentFakeIEntity::class,
            ChildFakeIEntity::class
        ]);

        parent::setUp();
    }

    function testMigrations()
    {
        $this->assertDatabaseEmpty(ParentFakeIEntity::getTableName());
        $this->assertDatabaseEmpty(ChildFakeIEntity::getTableName());
    }
}