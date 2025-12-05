<?php

namespace Tests\Unit\Repository;

use AppTank\Horus\Horus;
use AppTank\Horus\Repository\DefaultCacheRepository;
use AppTank\Horus\Repository\StaticMigrationSchemaRepository;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\TestCase;

class StaticMigrationSchemaRepositoryTest extends TestCase
{

    private StaticMigrationSchemaRepository $repository;

    function setUp(): void
    {
        parent::setUp();

        $this->repository = new StaticMigrationSchemaRepository(new DefaultCacheRepository());

        Horus::initialize([
            ParentFakeWritableEntity::class
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
