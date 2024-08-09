<?php

namespace Api;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\Feature\Api\ApiTestCase;

class GetDataEntitiesApiTest extends ApiTestCase
{
    use RefreshDatabase;

    function testGetDataEntitiesSuccess()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId));
        $childEntities = [];

        foreach ($parentsEntities as $parentEntity) {
            $childEntities[$parentEntity->getId()] = $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userId));
        }

        // When
        $response = $this->get(route(RouteName::GET_DATA_ENTITIES->value));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities));
    }

    function testGetDataEntitiesSuccessIsEmpty()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        // When
        $response = $this->get(route(RouteName::GET_DATA_ENTITIES->value));

        // Then
        $response->assertOk();
        $response->assertJsonCount(0);
    }
}