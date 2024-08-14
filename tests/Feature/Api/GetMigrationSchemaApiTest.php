<?php

namespace Tests\Feature\Api;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Repository\StaticMigrationSchemaRepository;
use AppTank\Horus\RouteName;
use Tests\_Stubs\AdjacentFakeEntity;
use Tests\_Stubs\ChildFakeEntity;
use Tests\_Stubs\ParentFakeEntity;

class GetMigrationSchemaApiTest extends ApiTestCase
{
    private const array JSON_SCHEMA = [
        '*' => [
            'entity',
            'attributes' => [
                '*' => [
                    'name',
                    'version',
                    'type',
                    'nullable',
                ]
            ],
            'current_version'
        ]
    ];

    function testGetMigrationSchemaIsSuccess()
    {
        $response = $this->get(route(RouteName::GET_MIGRATIONS->value));

        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEMA);
        $response->assertJson([ParentFakeEntity::schema()]);

        // Validate relations one of many
        $response->assertJsonPath("0.attributes.9.name", "relations_one_of_many");
        $response->assertJsonPath("0.attributes.9.related.0.entity", ChildFakeEntity::getEntityName());

        // Validate relations one of one
        $response->assertJsonPath("0.attributes.10.name", "relations_one_of_one");
        $response->assertJsonPath("0.attributes.10.related.0.entity", AdjacentFakeEntity::getEntityName());

        foreach ($response->json()[0]["attributes"] as $attribute) {
            if ($attribute["name"] == EntitySynchronizable::ATTR_SYNC_DELETED_AT) {
                $this->assertTrue(false, "The " . EntitySynchronizable::ATTR_SYNC_DELETED_AT . " attribute should not be present in the schema");
            }
        }
    }
}