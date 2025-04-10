<?php

namespace Tests\Feature\Api;

use AppTank\Horus\Core\Entity\EntityType;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use AppTank\Horus\RouteName;
use Tests\_Stubs\AdjacentFakeWritableEntity;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\ReadableFakeEntity;
use Tests\_Stubs\ParentFakeWritableEntity;

class GetMigrationSchemaApiTest extends ApiTestCase
{
    private const array JSON_SCHEMA = [
        '*' => [
            'entity',
            'type',
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
        $response->assertJson([ParentFakeWritableEntity::schema(),ReadableFakeEntity::schema()]);
        $response->assertJsonPath("0.type", EntityType::WRITABLE->value);
        $response->assertJsonPath("1.type", EntityType::READABLE->value);

        // Validate relations one of many
        $response->assertJsonPath("0.attributes.10.name", "relations_one_of_many");
        $response->assertJsonPath("0.attributes.10.related.0.entity", ChildFakeWritableEntity::getEntityName());
        $response->assertJsonPath("0.attributes.10.related.0.type", EntityType::WRITABLE->value);
        $response->assertJsonPath("0.attributes.10.related.0.attributes.12.linked_entity",ParentFakeWritableEntity::getEntityName());
        $response->assertJsonPath("0.attributes.10.related.0.attributes.12.delete_on_cascade", true);

        // Validate relations one of one
        $response->assertJsonPath("0.attributes.11.name", "relations_one_of_one");
        $response->assertJsonPath("0.attributes.11.related.0.entity", AdjacentFakeWritableEntity::getEntityName());

        foreach ($response->json()[0]["attributes"] as $attribute) {
            if ($attribute["name"] == WritableEntitySynchronizable::ATTR_SYNC_DELETED_AT) {
                $this->assertTrue(false, "The " . WritableEntitySynchronizable::ATTR_SYNC_DELETED_AT . " attribute should not be present in the schema");
            }
        }
    }
}