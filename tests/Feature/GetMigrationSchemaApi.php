<?php

namespace Tests\Feature;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\Repository\StaticMigrationSchemaRepository;
use Tests\_Stubs\FakeEntity;

class GetMigrationSchemaApi extends ApiTestCase
{

    function setUp(): void
    {
        parent::setUp();

        $this->repository = new StaticMigrationSchemaRepository();

        HorusContainer::initialize([
            FakeEntity::class
        ]);
    }

    function testApi()
    {
        $response = $this->get(route("horus.migration"));

        $response->assertOk();
    }
}