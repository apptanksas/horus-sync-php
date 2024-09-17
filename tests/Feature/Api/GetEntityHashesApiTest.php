<?php

namespace Api;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\Feature\Api\ApiTestCase;

class GetEntityHashesApiTest extends ApiTestCase
{
    use RefreshDatabase;

    function testEntityHashesIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($ownerId));
        /**
         * @var ParentFakeWritableEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_HASHES->value, [ParentFakeWritableEntity::getEntityName()]));
        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities));
        $response->assertExactJsonStructure([
            '*' => [
                WritableEntitySynchronizable::ATTR_ID,
                WritableEntitySynchronizable::ATTR_SYNC_HASH
            ]
        ]);
    }
}