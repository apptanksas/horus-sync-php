<?php

namespace Tests\Unit\Repository;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\Repository\StaticMigrationSchemaRepository;
use Tests\_Stubs\ParentFakeIEntity;
use PHPUnit\Framework\TestCase;

class StaticMigrationSchemaRepositoryTest extends TestCase
{

    private StaticMigrationSchemaRepository $repository;

    function setUp(): void
    {
        parent::setUp();

        $this->repository = new StaticMigrationSchemaRepository();

        HorusContainer::initialize([
            ParentFakeIEntity::class
        ]);
    }


    function testGetSchemaIsSuccess()
    {
        // When
        $schema = $this->repository->getSchema();

        // Then
        $this->assertIsArray($schema);
    }

}
