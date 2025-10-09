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
                SyncQueueActionModel::ATTR_BY_SYSTEM => false
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

    function testSaveWithUpDeleteActionIsSuccess()
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

}
