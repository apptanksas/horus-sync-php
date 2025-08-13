<?php

namespace Api;

use AppTank\Horus\Core\Auth\AccessLevel;
use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\UserActingAs;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
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

    function testEntityHashesOnlyOwnAndInvitedUsingActingIsSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $parentOwner = ParentFakeEntityFactory::create($userOwnerId);
        $ownerEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userOwnerId));
        $guestsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userGuestId));

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId,
                [new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner->getId()), AccessLevel::all())
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_HASHES->value, [ParentFakeWritableEntity::getEntityName()]));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($guestsEntities) + 1);
    }

    function testEntityHashesWithUserIdIsSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $parentOwner = ParentFakeEntityFactory::create($userOwnerId);
        $this->generateArray(fn() => ParentFakeEntityFactory::create($userOwnerId));
        $this->generateArray(fn() => ParentFakeEntityFactory::create($userGuestId));

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId,
                [new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner->getId()), AccessLevel::all())
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_HASHES->value, [ParentFakeWritableEntity::getEntityName(), "user_id" => $userOwnerId]));

        // Then
        $response->assertOk();
        $response->assertJsonCount(1); // Just the owner granted entity
    }


    function testEntityHashesWithUserIdUnAuthorizedIsSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;
        $userDifferentId = $this->faker->uuid;

        $parentOwner = ParentFakeEntityFactory::create($userOwnerId);
        $this->generateArray(fn() => ParentFakeEntityFactory::create($userOwnerId));
        $this->generateArray(fn() => ParentFakeEntityFactory::create($userGuestId));

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId,
                [new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner->getId()), AccessLevel::all())
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_HASHES->value, [ParentFakeWritableEntity::getEntityName(), "user_id" => $userDifferentId]));

        // Then
        $response->assertUnauthorized();
    }

}