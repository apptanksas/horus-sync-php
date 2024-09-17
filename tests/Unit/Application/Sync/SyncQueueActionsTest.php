<?php

namespace Tests\Unit\Application\Sync;

use AppTank\Horus\Application\Sync\SyncQueueActions;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Bus\IEventBus;
use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\Transaction\ITransactionHandler;
use AppTank\Horus\Illuminate\Transaction\EloquentTransactionHandler;
use Mockery\Mock;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\_Stubs\QueueActionFactory;
use Tests\TestCase;

class SyncQueueActionsTest extends TestCase
{
    private ITransactionHandler $transactionHandler;

    private QueueActionRepository|Mock $queueActionRepository;

    private EntityRepository|Mock $entityRepository;

    private EntityAccessValidatorRepository|Mock $accessValidatorRepository;

    private IEventBus|Mock $eventBus;

    private SyncQueueActions $syncQueueActions;

    public function setUp(): void
    {

        parent::setUp();

        $this->transactionHandler = new EloquentTransactionHandler();
        $this->queueActionRepository = $this->mock(QueueActionRepository::class);
        $this->entityRepository = $this->mock(EntityRepository::class);
        $this->eventBus = $this->mock(IEventBus::class);
        $this->accessValidatorRepository = $this->mock(EntityAccessValidatorRepository::class);

        $this->syncQueueActions = new SyncQueueActions(
            $this->transactionHandler,
            $this->queueActionRepository,
            $this->entityRepository,
            $this->accessValidatorRepository,
            $this->eventBus
        );
    }

    function testInvokeIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $insertActions = $this->generateArray(fn() => QueueActionFactory::create(
            EntityOperationFactory::createEntityInsert(
                $this->faker->uuid,
                ParentFakeWritableEntity::getEntityName(), ParentFakeEntityFactory::newData(), now()->toDateTimeImmutable()
            )
        ));
        $updateActions = $this->generateArray(fn() => QueueActionFactory::create(
            EntityOperationFactory::createEntityUpdate(
                $this->faker->uuid,
                ParentFakeWritableEntity::getEntityName(), $this->faker->uuid, ParentFakeEntityFactory::newData(), now()->toDateTimeImmutable()
            )
        ));
        $deleteActions = $this->generateArray(fn() => QueueActionFactory::create(
            EntityOperationFactory::createEntityDelete(
                $this->faker->uuid,
                ParentFakeWritableEntity::getEntityName(), $this->faker->uuid, now()->toDateTimeImmutable()
            )
        ));

        $actions = array_merge($insertActions, $updateActions, $deleteActions);
        shuffle($actions);


        // Mocks

        $this->accessValidatorRepository->shouldReceive("canAccessEntity")->times(count($updateActions) + count($deleteActions))->andReturn(true);

        $this->entityRepository->shouldReceive('insert')->once()->withArgs(function (...$args) use ($insertActions) {
            return count($args) === count($insertActions) &&
                array_reduce($args, fn($carry, $action) => $carry &&
                    $action instanceof EntityOperation, true);
        });
        $this->entityRepository->shouldReceive('update')->once()->withArgs(function (...$args) use ($updateActions) {
            return count($args) === count($updateActions) &&
                array_reduce($args, fn($carry, $action) => $carry &&
                    $action instanceof EntityOperation, true);
        });
        $this->entityRepository->shouldReceive('delete')->once()->withArgs(function (...$args) use ($deleteActions) {
            return count($args) === count($deleteActions) &&
                array_reduce($args, fn($carry, $action) => $carry &&
                    $action instanceof EntityOperation, true);
        });
        $this->queueActionRepository->shouldReceive('save')->once()->withArgs(function (...$args) use ($actions) {
            // Validate that args are QueueAction and the first item actionedAt is less than the last item actionedAt
            return count($args) === count($actions) &&
                array_reduce($args, fn($carry, $action) => $carry &&
                    $action instanceof QueueAction, true) &&
                $args[0]->actionedAt < $args[count($args) - 1]->actionedAt;
        });
        $this->eventBus->shouldReceive('publish')->times(count($actions));

        // When
        $this->syncQueueActions->__invoke(new UserAuth($userId), ...$actions);
    }
}
