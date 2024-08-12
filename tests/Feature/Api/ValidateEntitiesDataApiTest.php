<?php

namespace Api;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\HorusContainer;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ParentFakeEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\Feature\Api\ApiTestCase;

class ValidateEntitiesDataApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private const array JSON_SCHEME = [
        "*" => [
            "entity",
            "hash" => [
                "expected",
                "obtained",
                "matched"
            ]
        ]
    ];

    function testValidateEntitiesDataIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;

        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        $entities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId));

        $hashExpected = Hasher::hash(array_map(fn(ParentFakeEntity $entity) => Hasher::hash([
            ParentFakeEntity::ATTR_ID => $entity->getId(),
            ParentFakeEntity::ATTR_NAME => $entity->name,
            ParentFakeEntity::ATTR_COLOR => $entity->color,
            ParentFakeEntity::ATTR_VALUE_NULLABLE => $entity->{ParentFakeEntity::ATTR_VALUE_NULLABLE},
        ]), $entities));


        $data = [[
            'entity' => ParentFakeEntity::getEntityName(),
            'hash' => $hashExpected
        ]];

        // When
        $response = $this->post(route(RouteName::POST_VALIDATE_DATA->value), $data);

        // Given
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEME);
        $this->assertTrue($response->json("0.hash.matched"));
        $this->assertEquals($response->json("0.hash.obtained"), $response->json("0.hash.expected"));
    }


    function testValidateEntitiesDataIsNotMatched()
    {
        // Given
        $userId = $this->faker->uuid;

        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        $this->generateArray(fn() => ParentFakeEntityFactory::create($userId));

        $hashExpected = Hasher::hash(["id" => $this->faker->uuid]);


        $data = [[
            'entity' => ParentFakeEntity::getEntityName(),
            'hash' => $hashExpected
        ]];

        // When
        $response = $this->post(route(RouteName::POST_VALIDATE_DATA->value), $data);

        // Given
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEME);
        $this->assertFalse($response->json("0.hash.matched"));
        $this->assertNotEquals($response->json("0.hash.obtained"), $response->json("0.hash.expected"));
    }


}