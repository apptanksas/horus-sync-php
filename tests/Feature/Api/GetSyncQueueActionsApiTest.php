<?php

namespace Api;

use AppTank\Horus\Core\Auth\AccessLevel;
use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\UserActingAs;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\AdjacentFakeWritableEntity;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\NestedChildFakeEntityFactory;
use Tests\_Stubs\NestedChildFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\SyncQueueActionModelFactory;
use Tests\Feature\Api\ApiTestCase;

class GetSyncQueueActionsApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private const array JSON_SCHEME = [
        '*' => [
            'action',
            'entity',
            'data',
            'actioned_at',
            'synced_at'
        ]
    ];

    function testGetActionsIsSuccess()
    {

        // Given
        $userId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $actions = $this->generateArray(fn() => SyncQueueActionModelFactory::create(userId: $userId));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_ACTIONS->value));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($actions));
        $response->assertExactJsonStructure(self::JSON_SCHEME);
    }

    function testGetActionsAfterTimestampIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($ownerId));

        $syncedAt = $this->faker->dateTimeBetween()->getTimestamp();
        /**
         * @var SyncQueueActionModel[] $actions
         */
        $actions = $this->generateArray(fn() => SyncQueueActionModelFactory::create($ownerId, [
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt)
        ]));

        // Generate entities before the updatedAt
        $this->generateArray(fn() => SyncQueueActionModelFactory::create($ownerId, [
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($this->faker->dateTimeBetween(endDate: $syncedAt)->getTimestamp())
        ]));

        $syncedAtTarget = $syncedAt - 1;
        $countExpected = count(array_filter($actions, fn(SyncQueueActionModel $entity) => $entity->getSyncedAt()->getTimestamp() > $syncedAtTarget));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_ACTIONS->value, ["after" => $syncedAtTarget]));

        // Then
        $response->assertOk();
        $response->assertJsonCount($countExpected);
        $response->assertExactJsonStructure(self::JSON_SCHEME);
    }

    function testGetActionsFilterDateTimes()
    {
        $ownerId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($ownerId));
        /**
         * @var SyncQueueActionModel[] $parentsEntities
         */
        $actions = $this->generateCountArray(fn() => SyncQueueActionModelFactory::create($ownerId, [
            SyncQueueActionModel::ATTR_ACTIONED_AT => $this->getDateTimeUtil()->getFormatDate($this->faker->dateTimeBetween()->getTimestamp())
        ]));

        $excludeActions = array_map(fn(SyncQueueActionModel $entity) => $entity->getActionedAt()->getTimestamp(), array_slice($actions, 0, rand(1, 5)));
        $countExpected = count($actions) - count($excludeActions);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_ACTIONS->value, ["exclude" => join(",", $excludeActions)]));

        // Then
        $response->assertOk();
        $response->assertJsonCount($countExpected);
        $response->assertExactJsonStructure(self::JSON_SCHEME);
    }

    function testGetActionsOnlyOwnAndInvitedUsingActingIsSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $parentOwner = ParentFakeEntityFactory::create($userOwnerId);
        $childOwner = ChildFakeEntityFactory::create($parentOwner->getId(), $userOwnerId);

        $ownerActions = $this->generateArray(fn() => SyncQueueActionModelFactory::create($userOwnerId, [
            SyncQueueActionModel::ATTR_ENTITY => ParentFakeWritableEntity::getEntityName()
        ]));

        $ownerActionsFromChild = [SyncQueueActionModelFactory::create($userOwnerId, [
            SyncQueueActionModel::ATTR_ENTITY => ChildFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_ENTITY_ID => $childOwner->getId(),
            SyncQueueActionModel::ATTR_ACTION => SyncAction::UPDATE->value(),
            SyncQueueActionModel::ATTR_DATA => json_encode([
                "id" => $childOwner->getId(),
                "attributes" => [
                    ChildFakeWritableEntity::ATTR_INT_VALUE => $this->faker->randomNumber(),
                    ChildFakeWritableEntity::ATTR_STRING_VALUE => $this->faker->word,
                ]
            ])
        ])];

        $guestsActions = $this->generateArray(fn() => SyncQueueActionModelFactory::create($userGuestId, [
            SyncQueueActionModel::ATTR_ENTITY => AdjacentFakeWritableEntity::getEntityName()
        ]));

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId,
                [new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner->getId()), AccessLevel::all())
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_ACTIONS->value));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($guestsActions) + count($ownerActionsFromChild));
        $response->assertExactJsonStructure(self::JSON_SCHEME);
    }

    function testGetActionsOnlyOwnAndInvitedUsingActingWithAfterIsSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $parentOwner = ParentFakeEntityFactory::create($userOwnerId);
        $childOwner = ChildFakeEntityFactory::create($parentOwner->getId(), $userOwnerId);

        $syncedAt = $this->faker->dateTimeBetween()->getTimestamp();
        $syncedAtTarget = $syncedAt - 10;

        $ownerActions = $this->generateArray(fn() => SyncQueueActionModelFactory::create($userOwnerId, [
            SyncQueueActionModel::ATTR_ENTITY => ParentFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt)
        ]));

        $ownerActionsFromChild = [SyncQueueActionModelFactory::create($userOwnerId, [
            SyncQueueActionModel::ATTR_ENTITY => ChildFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_ENTITY_ID => $childOwner->getId(),
            SyncQueueActionModel::ATTR_ACTION => SyncAction::UPDATE->value(),
            SyncQueueActionModel::ATTR_DATA => json_encode([
                "id" => $childOwner->getId(),
                "attributes" => [
                    ChildFakeWritableEntity::ATTR_INT_VALUE => $this->faker->randomNumber(),
                    ChildFakeWritableEntity::ATTR_STRING_VALUE => $this->faker->word,
                ]
            ]),
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt)
        ])];

        $guestsActions = $this->generateArray(fn() => SyncQueueActionModelFactory::create($userGuestId, [
            SyncQueueActionModel::ATTR_ENTITY => AdjacentFakeWritableEntity::getEntityName(),
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt)
        ]));

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId,
                [new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner->getId()), AccessLevel::all())
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_ACTIONS->value, ["after" => $syncedAtTarget]));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($guestsActions) + count($ownerActionsFromChild));
        $response->assertExactJsonStructure(self::JSON_SCHEME);


        $data = $response->json();
        $this->assertTrue(array_values($data) === $data, "The JSON must be an array of objects");

        foreach ($data as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('action', $item);
            $this->assertArrayHasKey('entity', $item);
            $this->assertArrayHasKey('data', $item);
            $this->assertArrayHasKey('actioned_at', $item);
            $this->assertArrayHasKey('synced_at', $item);
        }
    }

    function testGetActionsWithEntityMoved()
    {
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $parentOwner = ParentFakeEntityFactory::create($userOwnerId);
        $parentOwner2 = ParentFakeEntityFactory::create($userOwnerId);
        $childOwner = ChildFakeEntityFactory::create($parentOwner2->getId(), $userOwnerId);
        $nestedChild = NestedChildFakeEntityFactory::create($childOwner->getId(), $userGuestId);

        $syncedAt = $this->faker->dateTimeBetween()->getTimestamp();
        $syncedAtTarget = $syncedAt - 10;

        $ownerActions = [
            SyncQueueActionModelFactory::create($userOwnerId, [
                SyncQueueActionModel::ATTR_ENTITY => NestedChildFakeWritableEntity::getEntityName(),
                SyncQueueActionModel::ATTR_ENTITY_ID => $nestedChild->getId(),
                SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt-5)
            ], action: SyncAction::INSERT),
            SyncQueueActionModelFactory::create($userOwnerId, [
                SyncQueueActionModel::ATTR_ENTITY => ChildFakeWritableEntity::getEntityName(),
                SyncQueueActionModel::ATTR_ENTITY_ID => $childOwner->getId(),
                SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt-4)
            ], action: SyncAction::UPDATE),
            SyncQueueActionModelFactory::create($userOwnerId, [
                SyncQueueActionModel::ATTR_ENTITY => ChildFakeWritableEntity::getEntityName(),
                SyncQueueActionModel::ATTR_ENTITY_ID => $childOwner->getId(),
                SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt-2)
            ], action: SyncAction::MOVE)
        ];


        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId,
                [new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner->getId()), AccessLevel::all())
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_ACTIONS->value, ["after" => $syncedAtTarget]));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($ownerActions));
        $response->assertExactJsonStructure(self::JSON_SCHEME);


        $data = $response->json();
        $this->assertTrue(array_values($data) === $data, "The JSON must be an array of objects");

        foreach ($data as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('action', $item);
            $this->assertArrayHasKey('entity', $item);
            $this->assertArrayHasKey('data', $item);
            $this->assertArrayHasKey('actioned_at', $item);
            $this->assertArrayHasKey('synced_at', $item);
        }
    }
}