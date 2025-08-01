<?php

namespace Tests\Unit\Application\Sync;

use AppTank\Horus\Application\Sync\SyncQueueActions;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Bus\IEventBus;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\Restriction\MaxCountEntityRestriction;
use AppTank\Horus\Core\Exception\OperationNotPermittedException;
use AppTank\Horus\Core\Exception\RestrictionException;
use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
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
        $this->fileHandler->shouldReceive("delete")->andReturn(true);
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
        $this->expectException(RestrictionException::class);

        $mapper = Horus::getInstance()->getEntityMapper();
        $config = new Config(true, entityRestrictions: [
            new MaxCountEntityRestriction(ParentFakeWritableEntity::getEntityName(), 1)
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

        $userId = $this->faker->uuid;
        $insertActions = $this->generateArray(function () {
            $parentData = ParentFakeEntityFactory::newData();
            return QueueActionFactory::create(
                EntityOperationFactory::createEntityInsert(
                    $this->faker->uuid,
                    ParentFakeWritableEntity::getEntityName(), $parentData, now()->toDateTimeImmutable()
                )
            );
        });

        $this->entityRepository->shouldReceive('getCount')->andReturn(1);
        $this->entityRepository->shouldReceive("getEntityParentOwner")->andReturn($this->faker->uuid);

        // When
        $syncQueueActions->__invoke(new UserAuth($userId), ...$insertActions);
    }
}
