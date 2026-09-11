<?php

namespace Api;

use AppTank\Horus\Core\Auth\AccessLevel;
use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\SyncQueueActionModelFactory;
use Tests\Feature\Api\ApiTestCase;

class GetQueueLastActionApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private const array JSON_SCHEME = [
        'action',
        'entity',
        'data',
        'actioned_at',
        'synced_at',
        'event_id'
    ];

    function testGetLastActionIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));
        $this->generateArray(fn() => SyncQueueActionModelFactory::create(userId: $userId));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_LAST_ACTION->value));

        // Then
        $response->assertOk();
        $response->assertExactJsonStructure(self::JSON_SCHEME);
    }

    function testGetLastActionConsidersActionsFromOwnersThatGrantedAccess()
    {
        // Given
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $parentOwner = ParentFakeEntityFactory::create($userOwnerId);

        // Guest's own action (older, since inserted first)
        SyncQueueActionModelFactory::create($userGuestId, [
            SyncQueueActionModel::FK_OWNER_ID => $userGuestId
        ]);

        // Owner's action on an entity granted to the guest (the most recent one, since inserted last)
        $lastAction = SyncQueueActionModelFactory::create($userOwnerId, [
            SyncQueueActionModel::ATTR_ENTITY => ParentFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_ENTITY_ID => $parentOwner->getId()
        ]);

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId, [
                new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner->getId()), AccessLevel::all())
            ])
        )->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_LAST_ACTION->value));

        // Then
        $response->assertOk();
        $response->assertExactJsonStructure(self::JSON_SCHEME);
        $response->assertJsonPath('event_id', $lastAction->getEventId());
    }

    function testGetLastActionSkipsInaccessibleActionsAndReturnsLastAccessibleOne()
    {
        // Given
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $grantedParent = ParentFakeEntityFactory::create($userOwnerId);
        $notGrantedParent = ParentFakeEntityFactory::create($userOwnerId);

        // The last accessible action for the guest (older, granted entity)
        $accessibleAction = SyncQueueActionModelFactory::create($userOwnerId, [
            SyncQueueActionModel::ATTR_ENTITY => ParentFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_ENTITY_ID => $grantedParent->getId()
        ]);

        // The most recent action overall, but on an entity not granted to the guest
        SyncQueueActionModelFactory::create($userOwnerId, [
            SyncQueueActionModel::ATTR_ENTITY => ParentFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_ENTITY_ID => $notGrantedParent->getId()
        ]);

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId, [
                new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $grantedParent->getId()), AccessLevel::all())
            ])
        )->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_LAST_ACTION->value));

        // Then
        $response->assertOk();
        $response->assertExactJsonStructure(self::JSON_SCHEME);
        $response->assertJsonPath('event_id', $accessibleAction->getEventId());
    }

    function testGetLastActionConsidersActionsFromMultipleGrantedOwners()
    {
        // Given
        $userOwnerId1 = $this->faker->uuid;
        $userOwnerId2 = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $parentOwner1 = ParentFakeEntityFactory::create($userOwnerId1);
        $parentOwner2 = ParentFakeEntityFactory::create($userOwnerId2);

        // Older action, from owner 1
        SyncQueueActionModelFactory::create($userOwnerId1, [
            SyncQueueActionModel::ATTR_ENTITY => ParentFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_ENTITY_ID => $parentOwner1->getId()
        ]);

        // Most recent action, from owner 2
        $lastAction = SyncQueueActionModelFactory::create($userOwnerId2, [
            SyncQueueActionModel::ATTR_ENTITY => ParentFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_ENTITY_ID => $parentOwner2->getId()
        ]);

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId, [
                new EntityGranted($userOwnerId1,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner1->getId()), AccessLevel::all()),
                new EntityGranted($userOwnerId2,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner2->getId()), AccessLevel::all())
            ])
        )->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_LAST_ACTION->value));

        // Then
        $response->assertOk();
        $response->assertExactJsonStructure(self::JSON_SCHEME);
        $response->assertJsonPath('event_id', $lastAction->getEventId());
    }

    function testGetLastActionConsidersAccessOnCascadeForChildEntities()
    {
        // Given
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $parentOwner = ParentFakeEntityFactory::create($userOwnerId);
        $childOwner = ChildFakeEntityFactory::create($parentOwner->getId(), $userOwnerId);

        // Older action, from the guest itself
        SyncQueueActionModelFactory::create($userGuestId, [
            SyncQueueActionModel::FK_OWNER_ID => $userGuestId
        ]);

        // Most recent action, from the owner, over a child entity of the granted parent
        $lastAction = SyncQueueActionModelFactory::create($userOwnerId, [
            SyncQueueActionModel::ATTR_ENTITY => ChildFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_ENTITY_ID => $childOwner->getId()
        ]);

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId, [
                new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner->getId()), AccessLevel::all())
            ])
        )->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_LAST_ACTION->value));

        // Then
        $response->assertOk();
        $response->assertExactJsonStructure(self::JSON_SCHEME);
        $response->assertJsonPath('event_id', $lastAction->getEventId());
    }

    function testGetLastActionReturnsEmptyArrayWhenThereIsNoAccessibleAction()
    {
        // Given
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $notGrantedParent = ParentFakeEntityFactory::create($userOwnerId);

        SyncQueueActionModelFactory::create($userOwnerId, [
            SyncQueueActionModel::ATTR_ENTITY => ParentFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_ENTITY_ID => $notGrantedParent->getId()
        ]);

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userGuestId))->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_LAST_ACTION->value));

        // Then
        $response->assertOk();
        $response->assertExactJson([]);
    }
}
