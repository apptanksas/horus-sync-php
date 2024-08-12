<?php

namespace Tests\Unit\Repository;


use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use AppTank\Horus\Repository\EloquentQueueActionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\QueueActionFactory;
use Tests\TestCase;

class EloquentQueueActionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentQueueActionRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentQueueActionRepository();
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
                SyncQueueActionModel::ATTR_ACTIONED_AT => $action->actionedAt->format('Y-m-d H:i:s'),
                SyncQueueActionModel::ATTR_SYNCED_AT => $action->syncedAt->format('Y-m-d H:i:s'),
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

}
