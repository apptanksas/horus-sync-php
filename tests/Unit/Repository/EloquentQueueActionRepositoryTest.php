<?php

namespace Tests\Unit\Repository;


use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use AppTank\Horus\Repository\EloquentQueueActionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\QueueActionFactory;
use Tests\_Stubs\SyncQueueActionModelFactory;
use Tests\TestCase;

class EloquentQueueActionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentQueueActionRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentQueueActionRepository(new DateTimeUtil());
    }

    function testSaveIsSuccess()
    {
        // Given
        /**
         * @var QueueAction[] $actions
         */
        $actions = $this->generateArray(fn() => QueueActionFactory::create());
        // When
        $this->repository->save(...$actions);
        // Then
        foreach ($actions as $action) {
            $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
                SyncQueueActionModel::ATTR_ACTION => $action->action->value,
                SyncQueueActionModel::ATTR_ENTITY => $action->entity,
                SyncQueueActionModel::ATTR_DATA => json_encode($action->operation->toArray()),
                SyncQueueActionModel::ATTR_ENTITY_ID => $action->operation->id,
                SyncQueueActionModel::ATTR_ACTIONED_AT => $action->actionedAt->format('Y-m-d H:i:s'),
                SyncQueueActionModel::ATTR_SYNCED_AT => $action->syncedAt->format('Y-m-d H:i:s'),
                SyncQueueActionModel::ATTR_BY_SYSTEM => false,
                SyncQueueActionModel::ATTR_SKIPPED => false
            ]);
        }
    }

    function testSaveIsSuccessWithSkipped()
    {
        // Given
        /**
         * @var QueueAction[] $actions
         */
        $actions = $this->generateArray(fn() => QueueActionFactory::create(skipped: true));
        // When
        $this->repository->save(...$actions);
        // Then
        foreach ($actions as $action) {
            $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
                SyncQueueActionModel::ATTR_ACTION => $action->action->value,
                SyncQueueActionModel::ATTR_ENTITY => $action->entity,
                SyncQueueActionModel::ATTR_DATA => json_encode($action->operation->toArray()),
                SyncQueueActionModel::ATTR_ENTITY_ID => $action->operation->id,
                SyncQueueActionModel::ATTR_ACTIONED_AT => $action->actionedAt->format('Y-m-d H:i:s'),
                SyncQueueActionModel::ATTR_SYNCED_AT => $action->syncedAt->format('Y-m-d H:i:s'),
                SyncQueueActionModel::ATTR_BY_SYSTEM => false,
                SyncQueueActionModel::ATTR_SKIPPED => true
            ]);
        }
    }

    function testSaveIsSuccessBySystemFlag()
    {
        // Given
        /**
         * @var QueueAction[] $actions
         */
        $actions = $this->generateArray(fn() => QueueActionFactory::create(bySystem: true));
        // When
        $this->repository->save(...$actions);
        // Then
        foreach ($actions as $action) {
            $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
                SyncQueueActionModel::ATTR_ACTION => $action->action->value,
                SyncQueueActionModel::ATTR_ENTITY => $action->entity,
                SyncQueueActionModel::ATTR_DATA => json_encode($action->operation->toArray()),
                SyncQueueActionModel::ATTR_ENTITY_ID => $action->operation->id,
                SyncQueueActionModel::ATTR_ACTIONED_AT => $action->actionedAt->format('Y-m-d H:i:s'),
                SyncQueueActionModel::ATTR_SYNCED_AT => $action->syncedAt->format('Y-m-d H:i:s'),
                SyncQueueActionModel::ATTR_BY_SYSTEM => true
            ]);
        }
    }


    function testGetLastActionIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $actions = $this->generateArray(fn() => QueueActionFactory::create(userId: $userId));

        $this->repository->save(...$actions);

        // When
        $lastAction = $this->repository->getLastAction($userId);

        // Then
        $this->assertNotNull($lastAction);
    }

    function testGetLastActionIsReturnNull()
    {
        // Given
        $userId = $this->faker->uuid;
        $actions = $this->generateArray(fn() => QueueActionFactory::create(userId: $userId));

        $this->repository->save(...$actions);

        // When
        $lastAction = $this->repository->getLastAction($this->faker->uuid);

        // Then
        $this->assertNull($lastAction);
    }

    function testGetActionsIsSuccess()
    {

        // Given
        $userId = $this->faker->uuid;
        $actions = $this->generateArray(fn() => QueueActionFactory::create(userId: $userId));
        $this->repository->save(...$actions);

        // When
        $actions = $this->repository->getActions($userId);

        // Then
        $this->assertCount(count($actions), $actions);
    }

    function testGetActionsAfterTimestampIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $syncedAt = $this->faker->dateTimeBetween()->getTimestamp();
        /**
         * @var SyncQueueActionModel[] $actions
         */
        $actions = $this->generateArray(fn() => SyncQueueActionModelFactory::create($ownerId, [
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt)
        ]));

        // Generate entities before the updatedAt
        $this->generateArray(function () use ($ownerId, $syncedAt) {
            $timestamp = $this->faker->dateTimeBetween(endDate: $syncedAt)->getTimestamp();
            return SyncQueueActionModelFactory::create($ownerId, [
                SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($timestamp)
            ]);
        });

        $syncedAtTarget = $syncedAt - 1;
        $countExpected = count(array_filter($actions, fn(SyncQueueActionModel $entity) => $entity->getSyncedAt()->getTimestamp() > $syncedAtTarget));

        // When
        $result = $this->repository->getActions($ownerId, $syncedAtTarget);

        // Then
        $this->assertCount($countExpected, $result);
    }

    function testGetActionsFilterDateTimes()
    {
        $ownerId = $this->faker->uuid;
        /**
         * @var SyncQueueActionModel[] $parentsEntities
         */
        $actions = $this->generateCountArray(fn() => SyncQueueActionModelFactory::create($ownerId, [
            SyncQueueActionModel::ATTR_ACTIONED_AT => $this->getDateTimeUtil()->getFormatDate($this->faker->dateTimeBetween()->getTimestamp())
        ]));

        $filterActions = array_map(fn(SyncQueueActionModel $entity) => $entity->getActionedAt()->getTimestamp(), array_slice($actions, 0, rand(1, 5)));
        $countExpected = count($actions) - count($filterActions);

        // When
        $result = $this->repository->getActions($ownerId, excludeDateTimes: $filterActions);

        // Then
        $this->assertCount($countExpected, $result);
    }

    function testGetActionsWithArrayUserIdsAfterTimestampIsSuccess()
    {
        $ownerId1 = $this->faker->uuid;
        $ownerId2 = $this->faker->uuid;
        $syncedAt = $this->faker->dateTimeBetween()->getTimestamp();
        /**
         * @var SyncQueueActionModel[] $actions
         */
        $actions = $this->generateArray(fn() => SyncQueueActionModelFactory::create($ownerId1, [
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt)
        ]));

        $actions2 = $this->generateArray(fn() => SyncQueueActionModelFactory::create($ownerId2, [
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt)
        ]));

        // Generate entities before the updatedAt
        $this->generateArray(function () use ($ownerId1, $syncedAt) {
            $timestamp = $this->faker->dateTimeBetween(endDate: $syncedAt)->getTimestamp();
            return SyncQueueActionModelFactory::create($ownerId1, [
                SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($timestamp)
            ]);
        });

        $this->generateArray(function () use ($ownerId2, $syncedAt) {
            $timestamp = $this->faker->dateTimeBetween(endDate: $syncedAt)->getTimestamp();
            return SyncQueueActionModelFactory::create($ownerId2, [
                SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($timestamp)
            ]);
        });

        $syncedAtTarget = $syncedAt - 1;
        $countExpected = count(array_filter(array_merge($actions, $actions2), fn(SyncQueueActionModel $entity) => $entity->getSyncedAt()->getTimestamp() > $syncedAtTarget));

        // When
        $result = $this->repository->getActions([$ownerId1, $ownerId2], $syncedAtTarget);

        // Then
        $this->assertCount($countExpected, $result);
    }


    function testGetActionsWithArrayUserOwnersIdsAfterTimestampUsingUserIdsAndExcludeIsSuccess()
    {
        $ownerId1 = $this->faker->uuid;
        $userId = $this->faker->uuid;
        $syncedAt = $this->faker->dateTimeBetween("-2 years")->getTimestamp();
        $excludeTimestamps = [$syncedAt];

        /**
         * @var SyncQueueActionModel[] $actions
         */
        $ownerActions = $this->generateCountArray(fn() => SyncQueueActionModelFactory::create($ownerId1, [
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt),
            SyncQueueActionModel::ATTR_ACTIONED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt),
            SyncQueueActionModel::FK_USER_ID => $ownerId1,
            SyncQueueActionModel::FK_OWNER_ID => $ownerId1,
        ]), rand(1, 5));

        $userActions = $this->generateCountArray(fn() => SyncQueueActionModelFactory::create($ownerId1, [
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt),
            SyncQueueActionModel::ATTR_ACTIONED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt),
            SyncQueueActionModel::FK_USER_ID => $userId,
            SyncQueueActionModel::FK_OWNER_ID => $ownerId1,
        ]), rand(1, 5));

        $syncedAtTarget = $syncedAt - 1;
        $countExpected = count($userActions);

        // When
        $result = $this->repository->getActions([$ownerId1], afterTimestamp: $syncedAtTarget, excludeDateTimes: $excludeTimestamps);

        // Then
        $this->assertCount($countExpected, $result);
    }

    function testSaveWithMoveActionIsSuccess()
    {
        // Given
        /**
         * @var QueueAction[] $actions
         */
        $actions = $this->generateArray(fn() => QueueActionFactory::create(action: SyncAction::MOVE));
        // When
        $this->repository->save(...$actions);
        // Then
        foreach ($actions as $action) {
            $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
                SyncQueueActionModel::ATTR_ACTION => SyncAction::MOVE->value,
                SyncQueueActionModel::ATTR_ENTITY => $action->entity,
                SyncQueueActionModel::ATTR_DATA => json_encode($action->operation->toArray()),
                SyncQueueActionModel::ATTR_ENTITY_ID => $action->operation->id,
                SyncQueueActionModel::ATTR_ACTIONED_AT => $action->actionedAt->format('Y-m-d H:i:s'),
                SyncQueueActionModel::ATTR_SYNCED_AT => $action->syncedAt->format('Y-m-d H:i:s'),
                SyncQueueActionModel::ATTR_BY_SYSTEM => false
            ]);
        }
    }

    function testGetActionsAfterTimestampIsSuccessWithSkipped()
    {
        $ownerId = $this->faker->uuid;
        $syncedAt = $this->faker->dateTimeBetween()->getTimestamp();
        /**
         * @var SyncQueueActionModel[] $actions
         */
        $actions = $this->generateArray(fn() => SyncQueueActionModelFactory::create($ownerId, [
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt),
            SyncQueueActionModel::ATTR_SKIPPED => true
        ]));

        // Generate entities before the updatedAt
        $this->generateArray(function () use ($ownerId, $syncedAt) {
            $timestamp = $this->faker->dateTimeBetween(endDate: $syncedAt)->getTimestamp();
            return SyncQueueActionModelFactory::create($ownerId, [
                SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($timestamp),
                SyncQueueActionModel::ATTR_SKIPPED => true
            ]);
        });

        $syncedAtTarget = $syncedAt - 1;

        // When
        $result = $this->repository->getActions($ownerId, $syncedAtTarget);

        // Then
        $this->assertCount(0, $result);
    }

    function testSaveWithEventIdInsertsNewRecord()
    {
        // Given
        $eventId = $this->faker->uuid;
        /** @var QueueAction $action */
        $action = QueueActionFactory::create(eventId: $eventId);

        // When
        $this->repository->save($action);

        // Then
        $this->assertDatabaseCount(SyncQueueActionModel::TABLE_NAME, 1);
        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::ATTR_EVENT_ID => $eventId,
            SyncQueueActionModel::ATTR_ACTION => $action->action->value,
            SyncQueueActionModel::ATTR_ENTITY => $action->entity,
            SyncQueueActionModel::ATTR_DATA => json_encode($action->operation->toArray()),
            SyncQueueActionModel::ATTR_ENTITY_ID => $action->operation->id,
            SyncQueueActionModel::ATTR_ACTIONED_AT => $action->actionedAt->format('Y-m-d H:i:s'),
            SyncQueueActionModel::ATTR_SYNCED_AT => $action->syncedAt->format('Y-m-d H:i:s'),
            SyncQueueActionModel::ATTR_BY_SYSTEM => false,
            SyncQueueActionModel::ATTR_SKIPPED => false
        ]);
    }

    function testSaveWithEventIdUpdatesExistingRecord()
    {
        // Given
        $eventId = $this->faker->uuid;
        $userId = $this->faker->uuid;
        /** @var QueueAction $action1 */
        $action1 = QueueActionFactory::create(userId: $userId, action: SyncAction::INSERT, skipped: false, eventId: $eventId);

        $this->repository->save($action1);
        $this->assertDatabaseCount(SyncQueueActionModel::TABLE_NAME, 1);

        /** @var QueueAction $action2 */
        $action2 = QueueActionFactory::create(userId: $userId, action: SyncAction::UPDATE, skipped: true, eventId: $eventId);

        // When
        $this->repository->save($action2);

        // Then
        $this->assertDatabaseCount(SyncQueueActionModel::TABLE_NAME, 1);
        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::ATTR_EVENT_ID => $eventId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::UPDATE->value,
            SyncQueueActionModel::ATTR_ENTITY => $action2->entity,
            SyncQueueActionModel::ATTR_DATA => json_encode($action2->operation->toArray()),
            SyncQueueActionModel::ATTR_ENTITY_ID => $action2->operation->id,
            SyncQueueActionModel::ATTR_ACTIONED_AT => $action2->actionedAt->format('Y-m-d H:i:s'),
            SyncQueueActionModel::ATTR_SYNCED_AT => $action2->syncedAt->format('Y-m-d H:i:s'),
            SyncQueueActionModel::ATTR_SKIPPED => true
        ]);
        $this->assertDatabaseMissing(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::ATTR_EVENT_ID => $eventId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::INSERT->value,
            SyncQueueActionModel::ATTR_ENTITY => $action1->entity,
        ]);
    }

    function testSaveMultipleActionsWithUniqueEventIdsInsertsAll()
    {
        // Given
        $actions = $this->generateArray(fn() => QueueActionFactory::create(eventId: $this->faker->uuid));

        // When
        $this->repository->save(...$actions);

        // Then
        $this->assertDatabaseCount(SyncQueueActionModel::TABLE_NAME, count($actions));
        foreach ($actions as $action) {
            $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
                SyncQueueActionModel::ATTR_EVENT_ID => $action->eventId,
                SyncQueueActionModel::ATTR_ACTION => $action->action->value,
                SyncQueueActionModel::ATTR_ENTITY => $action->entity,
                SyncQueueActionModel::ATTR_DATA => json_encode($action->operation->toArray()),
            ]);
        }
    }

    function testSaveWithNullEventIdMaintainsBackwardsCompatibility()
    {
        // Given
        $actions = $this->generateArray(fn() => QueueActionFactory::create(eventId: null));

        // When
        $this->repository->save(...$actions);

        // Then
        $this->assertDatabaseCount(SyncQueueActionModel::TABLE_NAME, count($actions));
        foreach ($actions as $action) {
            $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
                SyncQueueActionModel::ATTR_EVENT_ID => null,
                SyncQueueActionModel::ATTR_ACTION => $action->action->value,
                SyncQueueActionModel::ATTR_ENTITY => $action->entity,
                SyncQueueActionModel::ATTR_DATA => json_encode($action->operation->toArray()),
            ]);
        }
    }

    function testSaveMixedActionsWithAndWithoutEventId()
    {
        // Given
        $eventId1 = $this->faker->uuid;
        $eventId2 = $this->faker->uuid;
        $actionWithEvent1 = QueueActionFactory::create(action: SyncAction::INSERT, skipped: false, eventId: $eventId1);
        $actionWithoutEvent = QueueActionFactory::create(action: SyncAction::INSERT, eventId: null);
        $actionWithEvent2 = QueueActionFactory::create(action: SyncAction::INSERT, eventId: $eventId2);

        // When: saving initial batch with mixed actions
        $this->repository->save($actionWithEvent1, $actionWithoutEvent, $actionWithEvent2);

        // Then
        $this->assertDatabaseCount(SyncQueueActionModel::TABLE_NAME, 3);
        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::ATTR_EVENT_ID => $eventId1,
            SyncQueueActionModel::ATTR_SKIPPED => false,
        ]);
        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::ATTR_EVENT_ID => null,
            SyncQueueActionModel::ATTR_ENTITY => $actionWithoutEvent->entity,
        ]);
        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::ATTR_EVENT_ID => $eventId2,
            SyncQueueActionModel::ATTR_ENTITY => $actionWithEvent2->entity,
        ]);

        // When: updating eventId1 and inserting a new null eventId action
        $updatedActionWithEvent1 = QueueActionFactory::create(action: SyncAction::UPDATE, skipped: true, eventId: $eventId1);
        $newActionWithoutEvent = QueueActionFactory::create(action: SyncAction::DELETE, eventId: null);

        $this->repository->save($updatedActionWithEvent1, $newActionWithoutEvent);

        // Then: count should be 4 (3 existing - 1 updated + 1 new inserted)
        $this->assertDatabaseCount(SyncQueueActionModel::TABLE_NAME, 4);
        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::ATTR_EVENT_ID => $eventId1,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::UPDATE->value,
            SyncQueueActionModel::ATTR_SKIPPED => true,
        ]);
        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::ATTR_EVENT_ID => null,
            SyncQueueActionModel::ATTR_ENTITY => $newActionWithoutEvent->entity,
        ]);
    }

    function testGetLastActionAndGetActionsIncludesEventId()
    {
        // Given
        $userId = $this->faker->uuid;
        $eventId = $this->faker->uuid;
        $action = QueueActionFactory::create(userId: $userId, eventId: $eventId);

        $this->repository->save($action);

        // When
        $lastAction = $this->repository->getLastAction($userId);
        $actions = $this->repository->getActions($userId);

        // Then
        $this->assertNotNull($lastAction);
        $this->assertEquals($eventId, $lastAction->eventId);
        $this->assertCount(1, $actions);
        $this->assertEquals($eventId, $actions[0]->eventId);
    }

    function testGetActionsAfterEventIdIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        $baseDate = now()->subMinutes(10);
        $actions = [
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid, actionedAt: $baseDate->addMinute()->toDateTimeImmutable()),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid, actionedAt: $baseDate->addMinutes(2)->toDateTimeImmutable()),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid, actionedAt: $baseDate->addMinutes(3)->toDateTimeImmutable()),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid, actionedAt: $baseDate->addMinutes(4)->toDateTimeImmutable()),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid, actionedAt: $baseDate->addMinutes(5)->toDateTimeImmutable()),
        ];

        $this->repository->save(...$actions);

        $targetEventId = $actions[1]->eventId; // We want actions after action index 1 (i.e. indices 2, 3, 4)

        // When
        $result = $this->repository->getActions($ownerId, afterEventId: $targetEventId);

        // Then
        $this->assertCount(3, $result);
        $this->assertEquals($actions[2]->eventId, $result[0]->eventId);
        $this->assertEquals($actions[3]->eventId, $result[1]->eventId);
        $this->assertEquals($actions[4]->eventId, $result[2]->eventId);
    }

    function testGetActionsAfterEventIdWhenEventNotFoundReturnsEmpty()
    {
        // Given
        $ownerId = $this->faker->uuid;
        $actions = [
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid),
        ];

        $this->repository->save(...$actions);

        // When
        $result = $this->repository->getActions($ownerId, afterEventId: 'non-existent-event-id');

        // Then
        $this->assertCount(0, $result);
    }

    function testGetActionsFilterExcludeEventIdsIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        $actions = [
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid),
        ];

        $this->repository->save(...$actions);

        $excludeEventIds = [$actions[0]->eventId, $actions[2]->eventId];

        // When
        $result = $this->repository->getActions($ownerId, excludeEventIds: $excludeEventIds);

        // Then
        $this->assertCount(3, $result);
        $resultEventIds = array_map(fn(QueueAction $a) => $a->eventId, $result);
        $this->assertNotContains($actions[0]->eventId, $resultEventIds);
        $this->assertNotContains($actions[2]->eventId, $resultEventIds);
        $this->assertContains($actions[1]->eventId, $resultEventIds);
        $this->assertContains($actions[3]->eventId, $resultEventIds);
        $this->assertContains($actions[4]->eventId, $resultEventIds);
    }

    function testGetActionsFilterExcludeEventIdsWhenActorIsNotOwnerKeepsAction()
    {
        // Given
        $ownerId = $this->faker->uuid;
        $guestUserId = $this->faker->uuid;
        $eventId1 = $this->faker->uuid;
        $eventId2 = $this->faker->uuid;

        // Owner action (actor is owner)
        $ownerAction = QueueActionFactory::create(userId: $ownerId, eventId: $eventId1);
        // Guest action on owner's entity (actor is not owner)
        $guestAction = new QueueAction(
            $ownerAction->action,
            $ownerAction->entity,
            $ownerAction->entityId,
            $ownerAction->operation,
            $ownerAction->actionedAt,
            $ownerAction->syncedAt,
            $guestUserId,
            $ownerId,
            eventId: $eventId2
        );

        $this->repository->save($ownerAction, $guestAction);

        // When
        $result = $this->repository->getActions($ownerId, excludeEventIds: [$eventId1, $eventId2]);

        // Then: owner action is excluded, but guest action is kept because user_id != owner_id
        $this->assertCount(1, $result);
        $this->assertEquals($eventId2, $result[0]->eventId);
    }

    function testGetActionsWithAlwaysIncludeOwnerIdsAndExcludeEventIds()
    {
        // Given
        $ownerId1 = $this->faker->uuid;
        $ownerId2 = $this->faker->uuid;
        $eventId1 = $this->faker->uuid;
        $eventId2 = $this->faker->uuid;

        $action1 = QueueActionFactory::create(userId: $ownerId1, eventId: $eventId1);
        $action2 = QueueActionFactory::create(userId: $ownerId2, eventId: $eventId2);

        $this->repository->save($action1, $action2);

        // When: ownerId1 is in filteredOwnerIds (Group A), ownerId2 is in alwaysIncludeOwnerIds (Group B)
        $result = $this->repository->getActions(
            filteredOwnerIds: [$ownerId1],
            alwaysIncludeOwnerIds: [$ownerId2],
            excludeEventIds: [$eventId1, $eventId2]
        );

        // Then: Group A excludes eventId1, Group B unconditionally includes action2
        $this->assertCount(1, $result);
        $this->assertEquals($eventId2, $result[0]->eventId);
    }

    function testGetActionsWithBothAfterEventIdAndExcludeEventIds()
    {
        // Given
        $ownerId = $this->faker->uuid;
        $baseDate = now()->subMinutes(10);
        $actions = [
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid, actionedAt: $baseDate->addMinute()->toDateTimeImmutable()),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid, actionedAt: $baseDate->addMinutes(2)->toDateTimeImmutable()),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid, actionedAt: $baseDate->addMinutes(3)->toDateTimeImmutable()),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid, actionedAt: $baseDate->addMinutes(4)->toDateTimeImmutable()),
        ];

        $this->repository->save(...$actions);

        // When: after event 0, excluding event 2
        $result = $this->repository->getActions(
            filteredOwnerIds: $ownerId,
            afterEventId: $actions[0]->eventId,
            excludeEventIds: [$actions[2]->eventId]
        );

        // Then: should return event 1 and event 3
        $this->assertCount(2, $result);
        $this->assertEquals($actions[1]->eventId, $result[0]->eventId);
        $this->assertEquals($actions[3]->eventId, $result[1]->eventId);
    }
}
