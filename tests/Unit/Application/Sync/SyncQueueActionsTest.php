<?php

namespace Tests\Unit\Application\Sync;

use AppTank\Horus\Application\Sync\SyncQueueActions;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Bus\IEventBus;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\Restriction\MaxCountEntityRestriction;
use AppTank\Horus\Core\Config\Restriction\QueueActionSkipperValidatorEntityRestriction;
use AppTank\Horus\Core\Exception\RestrictionException;
use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\FileUploaded;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Core\Transaction\ITransactionHandler;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Transaction\EloquentTransactionHandler;
use Mockery\Mock;
use Tests\_Stubs\FileUploadedFactory;
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

    private FileUploadedRepository|Mock $fileUploadedRepository;

    private IEventBus|Mock $eventBus;

    private IFileHandler|Mock $fileHandler;

    private SyncQueueActions $syncQueueActions;

    public function setUp(): void
    {

        parent::setUp();

        $mapper = Horus::getInstance()->getEntityMapper();
        $config = new Config(true);

        $this->transactionHandler = new EloquentTransactionHandler();
        $this->queueActionRepository = $this->mock(QueueActionRepository::class);
        $this->entityRepository = $this->mock(EntityRepository::class);
        $this->eventBus = $this->mock(IEventBus::class);
        $this->accessValidatorRepository = $this->mock(EntityAccessValidatorRepository::class);
        $this->fileUploadedRepository = $this->mock(FileUploadedRepository::class);
        $this->fileHandler = $this->mock(IFileHandler::class);

        $this->syncQueueActions = new SyncQueueActions(
            $this->transactionHandler,
            $this->queueActionRepository,
            $this->entityRepository,
            $this->accessValidatorRepository,
            $this->fileUploadedRepository,
            $this->eventBus,
            $this->fileHandler,
            $mapper,
            $config
        );
    }

    function testInvokeIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        /**
         * @var FileUploaded[] $filesUploaded
         */
        $filesUploaded = array();

        $insertActions = $this->generateArray(function () use (&$filesUploaded) {
            $parentData = ParentFakeEntityFactory::newData();

            $action = QueueActionFactory::create(
                EntityOperationFactory::createEntityInsert(
                    $this->faker->uuid,
                    ParentFakeWritableEntity::getEntityName(), $parentData, now()->toDateTimeImmutable()
                )
            );

            $fileId = $parentData[ParentFakeWritableEntity::ATTR_IMAGE];
            $filesUploaded[$fileId] = FileUploadedFactory::create($fileId);

            return $action;
        });
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

        $this->entityRepository->shouldReceive("getEntityOwner")->andReturn($this->faker->uuid);
        $this->fileHandler->shouldReceive("copy")->andReturn(true);

        foreach ($filesUploaded as $fileId => $fileUploaded) {
            $this->fileHandler->shouldReceive("delete")->with($fileUploaded->path)->andReturn(true);
        }

        $this->entityRepository->shouldReceive("getEntityPathHierarchy")->andReturn([ParentFakeEntityFactory::create()]);
        $this->entityRepository->shouldReceive("getEntityParentOwner")->andReturn($this->faker->uuid);

        $this->fileHandler->shouldReceive("generateUrl")->andReturn($this->faker->imageUrl);
        $this->fileUploadedRepository->shouldReceive("save")->times(count($filesUploaded));

        foreach ($filesUploaded as $fileId => $fileUploaded) {
            $this->fileUploadedRepository->shouldReceive("search")->with($fileId)->andReturn($fileUploaded);
        }

        $this->eventBus->shouldReceive('publish')->times(count($actions));

        // When
        $this->syncQueueActions->__invoke(new UserAuth($userId), ...$actions);
    }


    function testInvokeIsFailureByMaxCountEntityExceeded()
    {
        $userId = $this->faker->uuid;
        $this->expectException(RestrictionException::class);

        $mapper = Horus::getInstance()->getEntityMapper();
        $config = new Config(true, entityRestrictions: [
            new MaxCountEntityRestriction($userId, ParentFakeWritableEntity::getEntityName(), 1)
        ]);

        $syncQueueActions = new SyncQueueActions(
            $this->transactionHandler,
            $this->queueActionRepository,
            $this->entityRepository,
            $this->accessValidatorRepository,
            $this->fileUploadedRepository,
            $this->eventBus,
            $this->fileHandler,
            $mapper,
            $config
        );

        $insertActions = $this->generateArray(function () use ($userId) {
            $parentData = ParentFakeEntityFactory::newData();
            return QueueActionFactory::create(
                EntityOperationFactory::createEntityInsert(
                    $userId,
                    ParentFakeWritableEntity::getEntityName(), $parentData, now()->toDateTimeImmutable()
                ),
                $userId
            );
        });

        $this->entityRepository->shouldReceive('getCount')->andReturn(1);
        $this->entityRepository->shouldReceive("getEntityParentOwner")->andReturn($this->faker->uuid);

        // When
        $syncQueueActions->__invoke(new UserAuth($userId), ...$insertActions);
    }

    function testInvokeIsSuccessValidateInsertAndDeleteRestrictions()
    {
        $mapper = Horus::getInstance()->getEntityMapper();
        $filesUploaded = array();
        $userId = $this->faker->uuid;

        $maxCount = 5;
        $config = new Config(true, entityRestrictions: [
            new MaxCountEntityRestriction($userId, ParentFakeWritableEntity::getEntityName(), $maxCount)
        ]);
        $ownerId = $this->faker->uuid;

        $syncQueueActions = new SyncQueueActions(
            $this->transactionHandler,
            $this->queueActionRepository,
            $this->entityRepository,
            $this->accessValidatorRepository,
            $this->fileUploadedRepository,
            $this->eventBus,
            $this->fileHandler,
            $mapper,
            $config
        );

        $insertActions = $this->generateCountArray(function () use ($ownerId, &$filesUploaded) {
            $parentData = ParentFakeEntityFactory::newData();

            $fileId = $parentData[ParentFakeWritableEntity::ATTR_IMAGE];
            $filesUploaded[$fileId] = FileUploadedFactory::create($fileId);

            return QueueActionFactory::create(
                EntityOperationFactory::createEntityInsert(
                    $ownerId,
                    ParentFakeWritableEntity::getEntityName(),
                    $parentData, now()->toDateTimeImmutable()
                ),
                $ownerId
            );
        }, $maxCount);

        $deleteActions = array_map(function (QueueAction $action) {
            return QueueActionFactory::create(
                EntityOperationFactory::createEntityDelete(
                    $action->ownerId,
                    ParentFakeWritableEntity::getEntityName(),
                    $action->entityId,
                    now()->addSeconds(10)->toDateTimeImmutable()
                ),
                $action->ownerId
            );
        }, $insertActions);

        $actions = array_merge($insertActions, $deleteActions);

        $this->entityRepository->shouldReceive('getCount')->andReturn($maxCount);
        $this->accessValidatorRepository->shouldReceive("canAccessEntity")->andReturn(true);

        $this->entityRepository->shouldReceive('insert')->once()->withArgs(function (...$args) use ($insertActions) {
            return count($args) === count($insertActions) &&
                array_reduce($args, fn($carry, $action) => $carry &&
                    $action instanceof EntityOperation, true);
        });
        $this->entityRepository->shouldReceive('delete')->once()->withArgs(function (...$args) use ($deleteActions) {
            return count($args) === count($deleteActions) &&
                array_reduce($args, fn($carry, $action) => $carry &&
                    $action instanceof EntityOperation, true);
        });

        $this->entityRepository->shouldReceive("update")->once()->andReturns();

        foreach ($filesUploaded as $fileId => $fileUploaded) {
            $this->fileUploadedRepository->shouldReceive("search")->with($fileId)->andReturn($fileUploaded);
        }
        $this->fileHandler->shouldReceive("generateUrl")->andReturn($this->faker->imageUrl);
        $this->fileUploadedRepository->shouldReceive("save")->times(count($filesUploaded));

        $this->queueActionRepository->shouldReceive('save')->once()->withArgs(function (...$args) use ($actions) {
            // Validate that args are QueueAction and the first item actionedAt is less than the last item actionedAt
            return count($args) === count($actions) &&
                array_reduce($args, fn($carry, $action) => $carry &&
                    $action instanceof QueueAction, true) &&
                $args[0]->actionedAt < $args[count($args) - 1]->actionedAt;
        });


        $this->entityRepository->shouldReceive("getEntityOwner")->andReturn($ownerId);
        $this->fileHandler->shouldReceive("copy")->andReturn(true);
        $this->fileHandler->shouldReceive("delete")->andReturn(true);
        $this->entityRepository->shouldReceive("getEntityPathHierarchy")->andReturn([ParentFakeEntityFactory::create()]);
        $this->entityRepository->shouldReceive("getEntityParentOwner")->andReturn($ownerId);

        $this->eventBus->shouldReceive('publish')->times(count($actions));

        // When
        $syncQueueActions->__invoke(new UserAuth($userId), ...$actions);

        // Then
        $this->assertTrue(true); // If no exception is thrown, the test is successful
    }

    function testInvokeIsSuccessWithSkippedActions()
    {
        $userId = $this->faker->uuid;
        $mapper = Horus::getInstance()->getEntityMapper();

        // Restriction that skips all actions for ParentFakeWritableEntity
        $config = new Config(true, entityRestrictions: [
            new QueueActionSkipperValidatorEntityRestriction(
                ParentFakeWritableEntity::getEntityName(),
                fn() => true
            )
        ]);

        $syncQueueActions = new SyncQueueActions(
            $this->transactionHandler,
            $this->queueActionRepository,
            $this->entityRepository,
            $this->accessValidatorRepository,
            $this->fileUploadedRepository,
            $this->eventBus,
            $this->fileHandler,
            $mapper,
            $config
        );

        $action = QueueActionFactory::create(
            EntityOperationFactory::createEntityInsert(
                $userId,
                ParentFakeWritableEntity::getEntityName(),
                ParentFakeEntityFactory::newData(),
                now()->toDateTimeImmutable()
            ),
            $userId
        );

        $this->entityRepository->shouldReceive('insert')->once()->withArgs(function (...$args) use ($action) {
            return empty($args);
        });
        $this->entityRepository->shouldReceive('update')->once()->withArgs(function (...$args) use ($action) {
            return empty($args);
        });
        $this->entityRepository->shouldReceive('delete')->once()->withArgs(function (...$args) use ($action) {
            return empty($args);
        });

        // But the action SHOULD be saved (with skipped flag)
        $this->queueActionRepository->shouldReceive('save')->once()->withArgs(function (QueueAction $savedAction) {
            return $savedAction->skipped === true;
        });

        $this->eventBus->shouldNotReceive('publish');

        // When
        $syncQueueActions->__invoke(new UserAuth($userId), $action);
    }

    function testInvokeWithPartialSkippedActions()
    {
        $userId = $this->faker->uuid;
        $mapper = Horus::getInstance()->getEntityMapper();

        // Restriction that skips only INSERT actions
        $config = new Config(true, entityRestrictions: [
            new QueueActionSkipperValidatorEntityRestriction(
                ParentFakeWritableEntity::getEntityName(),
                fn(SyncAction $action) => $action === SyncAction::INSERT
            )
        ]);

        $syncQueueActions = new SyncQueueActions(
            $this->transactionHandler,
            $this->queueActionRepository,
            $this->entityRepository,
            $this->accessValidatorRepository,
            $this->fileUploadedRepository,
            $this->eventBus,
            $this->fileHandler,
            $mapper,
            $config
        );

        $insertAction = QueueActionFactory::create(
            EntityOperationFactory::createEntityInsert(
                $userId,
                ParentFakeWritableEntity::getEntityName(),
                ParentFakeEntityFactory::newData(),
                now()->toDateTimeImmutable()
            ),
            $userId
        );

        $updateAction = QueueActionFactory::create(
            EntityOperationFactory::createEntityUpdate(
                $userId,
                ParentFakeWritableEntity::getEntityName(),
                $this->faker->uuid,
                ParentFakeEntityFactory::newData(),
                now()->addMinute()->toDateTimeImmutable()
            ),
            $userId
        );


        $this->entityRepository->shouldReceive('insert')->once()->withArgs(function (...$args)  {
            return empty($args);
        });
        $this->entityRepository->shouldReceive('delete')->once()->withArgs(function (...$args)  {
            return empty($args);
        });
        $this->entityRepository->shouldReceive('update')->once();
        $this->entityRepository->shouldReceive('getEntityOwner')->andReturn($userId);
        $this->accessValidatorRepository->shouldReceive('canAccessEntity')->andReturn(true);

        // BOTH actions should be saved
        $this->queueActionRepository->shouldReceive('save')->once()->withArgs(function (...$args) {
            $skipped = array_filter($args, fn(QueueAction $a) => $a->skipped);
            $notSkipped = array_filter($args, fn(QueueAction $a) => !$a->skipped);
            return count($args) === 2 && count($skipped) === 1 && count($notSkipped) === 1;
        });

        // Event should be published ONLY for UPDATE
        $this->eventBus->shouldReceive('publish')->once()->with("sync.update", \Mockery::any());

        // When
        $syncQueueActions->__invoke(new UserAuth($userId), $insertAction, $updateAction);
    }

    function testInvokeWithSkippedActionByData()
    {
        $userId = $this->faker->uuid;
        $mapper = Horus::getInstance()->getEntityMapper();

        // Restriction that skips only if data has 'should_skip' => true
        $config = new Config(true, entityRestrictions: [
            new QueueActionSkipperValidatorEntityRestriction(
                ParentFakeWritableEntity::getEntityName(),
                fn(SyncAction $action, EntityData $data) => ($data->getData()['should_skip'] ?? false) === true
            )
        ]);

        $syncQueueActions = new SyncQueueActions(
            $this->transactionHandler,
            $this->queueActionRepository,
            $this->entityRepository,
            $this->accessValidatorRepository,
            $this->fileUploadedRepository,
            $this->eventBus,
            $this->fileHandler,
            $mapper,
            $config
        );

        $actionToSkip = QueueActionFactory::create(
            EntityOperationFactory::createEntityInsert(
                $userId,
                ParentFakeWritableEntity::getEntityName(),
                array_merge(ParentFakeEntityFactory::newData(), ['should_skip' => true]),
                now()->toDateTimeImmutable()
            ),
            $userId
        );

        $actionToKeep = QueueActionFactory::create(
            EntityOperationFactory::createEntityInsert(
                $userId,
                ParentFakeWritableEntity::getEntityName(),
                array_merge(ParentFakeEntityFactory::newData(), ['should_skip' => false]),
                now()->addMinute()->toDateTimeImmutable()
            ),
            $userId
        );

        // Mocks
        $this->entityRepository->shouldReceive('insert')->once()->withArgs(function (EntityOperation $op) use ($actionToKeep) {
            return $op->id === $actionToKeep->operation->id;
        });

        $this->entityRepository->shouldReceive('update')->once()->withArgs(function (...$args)  {
            return empty($args);
        });

        $this->entityRepository->shouldReceive('delete')->once()->withArgs(function (...$args)  {
            return empty($args);
        });

        $this->entityRepository->shouldReceive("getEntityParentOwner")->andReturn($userId);

        $this->queueActionRepository->shouldReceive('save')->once()->withArgs(function (...$args) use ($actionToSkip, $actionToKeep) {
            return count($args) === 2;
        });

        $this->eventBus->shouldReceive('publish')->once();

        // Mocks for file validation (since newData() generates an image field)
        $this->fileUploadedRepository->shouldReceive('search')->andReturn(FileUploadedFactory::create($this->faker->uuid));
        $this->fileHandler->shouldReceive('generateUrl')->andReturn($this->faker->imageUrl);
        $this->fileUploadedRepository->shouldReceive('save');
        $this->fileHandler->shouldReceive('copy')->andReturn(true);
        $this->fileHandler->shouldReceive('delete')->andReturn(true);
        $this->entityRepository->shouldReceive('getEntityPathHierarchy')->andReturn([ParentFakeEntityFactory::create()]);

        // When
        $syncQueueActions->__invoke(new UserAuth($userId), $actionToSkip, $actionToKeep);
    }

    function testInvokeExecutesOnlyRegisteredEventActionsAndLegacyActions()
    {
        $userId = $this->faker->uuid;
        $registeredEventId = $this->faker->uuid;
        $unregisteredEventId = $this->faker->uuid;

        $registeredAction = QueueActionFactory::create(
            EntityOperationFactory::createEntityUpdate(
                $userId,
                ParentFakeWritableEntity::getEntityName(),
                $this->faker->uuid,
                ParentFakeEntityFactory::newData(),
                now()->toDateTimeImmutable()
            ),
            $userId,
            eventId: $registeredEventId,
            actionedAt: now()->toDateTimeImmutable()
        );
        $unregisteredAction = QueueActionFactory::create(
            EntityOperationFactory::createEntityUpdate(
                $userId,
                ParentFakeWritableEntity::getEntityName(),
                $this->faker->uuid,
                ParentFakeEntityFactory::newData(),
                now()->addMinute()->toDateTimeImmutable()
            ),
            $userId,
            eventId: $unregisteredEventId,
            actionedAt: now()->addMinute()->toDateTimeImmutable()
        );
        $legacyAction = QueueActionFactory::create(
            EntityOperationFactory::createEntityUpdate(
                $userId,
                ParentFakeWritableEntity::getEntityName(),
                $this->faker->uuid,
                ParentFakeEntityFactory::newData(),
                now()->addMinutes(2)->toDateTimeImmutable()
            ),
            $userId,
            actionedAt: now()->addMinutes(2)->toDateTimeImmutable()
        );

        $this->queueActionRepository->shouldReceive('checkExistsByEventIds')
            ->once()
            ->with([$registeredEventId, $unregisteredEventId])
            ->andReturn([
                $registeredEventId => true,
                $unregisteredEventId => false,
            ]);
        $this->accessValidatorRepository->shouldReceive('canAccessEntity')->twice()->andReturn(true);
        $this->entityRepository->shouldReceive('getEntityOwner')->twice()->andReturn($userId);
        $this->entityRepository->shouldReceive('insert')->once()->withArgs(fn(...$args) => empty($args));
        $this->entityRepository->shouldReceive('delete')->once()->withArgs(fn(...$args) => empty($args));
        $this->entityRepository->shouldReceive('update')->once()->withArgs(function (...$args) use ($registeredAction, $legacyAction) {
            return count($args) === 2 &&
                $args[0]->id === $registeredAction->operation->id &&
                $args[1]->id === $legacyAction->operation->id;
        });
        $this->queueActionRepository->shouldReceive('save')->once()->withArgs(function (...$args) use ($registeredEventId, $legacyAction) {
            return count($args) === 2 &&
                $args[0]->eventId === $registeredEventId &&
                $args[1]->eventId === $legacyAction->eventId;
        });
        $this->eventBus->shouldReceive('publish')->twice()->with('sync.update', \Mockery::any());

        // When
        $this->syncQueueActions->__invoke(new UserAuth($userId), $registeredAction, $unregisteredAction, $legacyAction);
    }

    function testInvokeDoesNothingWhenAllEventActionsAreUnregistered()
    {
        $userId = $this->faker->uuid;
        $eventId = $this->faker->uuid;
        $action = QueueActionFactory::create(
            EntityOperationFactory::createEntityUpdate(
                $userId,
                ParentFakeWritableEntity::getEntityName(),
                $this->faker->uuid,
                ParentFakeEntityFactory::newData(),
                now()->toDateTimeImmutable()
            ),
            $userId,
            eventId: $eventId
        );

        $this->queueActionRepository->shouldReceive('checkExistsByEventIds')
            ->once()
            ->with([$eventId])
            ->andReturn([$eventId => false]);
        $this->entityRepository->shouldNotReceive('insert');
        $this->entityRepository->shouldNotReceive('update');
        $this->entityRepository->shouldNotReceive('delete');
        $this->queueActionRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        // When
        $this->syncQueueActions->__invoke(new UserAuth($userId), $action);
    }
}
