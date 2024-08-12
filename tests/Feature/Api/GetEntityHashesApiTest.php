<?php

namespace Api;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ParentFakeEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\Feature\Api\ApiTestCase;

class GetEntityHashesApiTest extends ApiTestCase
{
    use RefreshDatabase;

    function testEntityHashesIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($ownerId);
        /**
         * @var ParentFakeEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_HASHES->value, [ParentFakeEntity::getEntityName()]));
        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities));
        $response->assertExactJsonStructure([
            '*' => [
                EntitySynchronizable::ATTR_ID,
                EntitySynchronizable::ATTR_SYNC_HASH
            ]
        ]);
    }
}