<?php

namespace Tests\Feature\Api;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Repository\StaticMigrationSchemaRepository;
use AppTank\Horus\RouteName;
use Tests\_Stubs\ParentFakeEntity;

class GetMigrationSchemaApiTest extends ApiTestCase
{
    private const JSON_SCHEMA = [
        '*' => [
            'entity',
            'attributes' => [
                '*' => [
                    'name',
                    'version',
                    'type',
                    'nullable'
                ]
            ],
            'current_version'
        ]
    ];

    function setUp(): void
    {
        parent::setUp();

        $this->repository = new StaticMigrationSchemaRepository();

        HorusContainer::initialize([
            ParentFakeEntity::class
        ]);
    }

    function testGetMigrationSchemaIsSuccess()
    {
        $response = $this->get(route(RouteName::GET_MIGRATIONS->value));

        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEMA);
        $response->assertJson([ParentFakeEntity::schema()]);

        foreach ($response->json()[0]["attributes"] as $attribute) {
            if ($attribute["name"] == EntitySynchronizable::ATTR_SYNC_DELETED_AT) {
                $this->assertTrue(false, "The " . EntitySynchronizable::ATTR_SYNC_DELETED_AT . " attribute should not be present in the schema");
            }
        }
    }
}