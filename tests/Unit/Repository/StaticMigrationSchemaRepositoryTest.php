<?php

namespace Tests\Unit\Repository;

use AppTank\Horus\Horus;
use AppTank\Horus\Repository\StaticMigrationSchemaRepository;
use Tests\_Stubs\ParentFakeEntity;
use PHPUnit\Framework\TestCase;

class StaticMigrationSchemaRepositoryTest extends TestCase
{

    private StaticMigrationSchemaRepository $repository;

    function setUp(): void
    {
        parent::setUp();

        $this->repository = new StaticMigrationSchemaRepository();

        Horus::initialize([
            ParentFakeEntity::class
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
