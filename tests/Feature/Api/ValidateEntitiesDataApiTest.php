<?php

namespace Api;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Horus;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ParentFakeWritableEntity;
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

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $entities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId));

        $hashExpected = Hasher::hash(array_map(fn(ParentFakeWritableEntity $entity) => Hasher::hash([
            ParentFakeWritableEntity::ATTR_ID => $entity->getId(),
            ParentFakeWritableEntity::ATTR_NAME => $entity->name,
            ParentFakeWritableEntity::ATTR_COLOR => $entity->color,
            ParentFakeWritableEntity::ATTR_TIMESTAMP => $entity->timestamp,
            ParentFakeWritableEntity::ATTR_ENUM => $entity->value_enum,
            ParentFakeWritableEntity::ATTR_VALUE_NULLABLE => $entity->{ParentFakeWritableEntity::ATTR_VALUE_NULLABLE},
            ParentFakeWritableEntity::ATTR_IMAGE => $entity->image
        ]), $entities));


        $data = [[
            'entity' => ParentFakeWritableEntity::getEntityName(),
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

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $this->generateArray(fn() => ParentFakeEntityFactory::create($userId));

        $hashExpected = Hasher::hash(["id" => $this->faker->uuid]);


        $data = [[
            'entity' => ParentFakeWritableEntity::getEntityName(),
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