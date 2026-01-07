<?php

namespace Tests\_Stubs\Client;

use AppTank\Horus\Client\HorusQueueActionClient;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\Restriction\MaxCountEntityRestriction;
use AppTank\Horus\Core\Exception\RestrictionException;
use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Core\Transaction\ITransactionHandler;
use AppTank\Horus\Illuminate\Transaction\EloquentTransactionHandler;
use AppTank\Horus\Repository\EloquentEntityRepository;
use AppTank\Horus\Repository\EloquentQueueActionRepository;
use DateTimeImmutable;
use Mockery\Mock;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\SyncQueueActionModelFactory;
use Tests\TestCase;

class HorusQueueActionClientTest extends TestCase
{
    private ITransactionHandler $transactionHandler;
    private EntityRepository|Mock $entityRepository;
    private QueueActionRepository|Mock $queueActionRepository;
    private Config $config;
    private HorusQueueActionClient $horusQueueActionClient;


    public function setUp(): void
    {
        parent::setUp();

        $this->transactionHandler = new EloquentTransactionHandler();
        $this->entityRepository = $this->mock(EntityRepository::class);
        $this->queueActionRepository = $this->mock(QueueActionRepository::class);
        $this->config = new Config(true);

        $this->horusQueueActionClient = new HorusQueueActionClient(
            $this->transactionHandler,
            $this->queueActionRepository,
            $this->entityRepository,
            $this->config
        );
    }

    function testGetIsLastActionSuccess()
    {

        $action = SyncQueueActionModelFactory::create();

        // When
        $result = $this->horusQueueActionClient->getLastActionByEntity(SyncAction::from($action->getAction()), $action->getEntity(), $action->getEntityId());

        // Then
        $this->assertNotNull($result);
        $this->assertEquals($action->action, $result->action->value());
        $this->assertEquals($action->getEntity(), $result->entity);
        $this->assertEquals($action->getEntityId(), $result->entityId);
    }

    function testGetIsLastActionFail()
    {
        // Given
        $action = SyncQueueActionModelFactory::create();

        // When
        $result = $this->horusQueueActionClient->getLastActionByEntity(SyncAction::from($action->getAction()), 'fake_entity', 'fake_id');

        // Then
        $this->assertNull($result);
    }

    function testPushActionsSuccess()
    {
        // Given
        $insertActions = $this->generateTestActions(EntityInsert::class, 2);
        $updateActions = $this->generateTestActions(EntityUpdate::class, 2);
        $deleteActions = $this->generateTestActions(EntityDelete::class, 2);

        $allActions = array_merge($insertActions, $updateActions, $deleteActions);
        shuffle($allActions);

        // Setup repository expectations
        $this->entityRepository->shouldReceive('insert')
            ->once()
            ->withArgs(function (...$args) use ($insertActions) {
                if (count($args) !== count($insertActions)) {
                    return false;
                }
                foreach ($args as $index => $arg) {
                    if (!($arg instanceof EntityOperation)) {
                        return false;
                    }
                }
                return true;
            });

        $this->entityRepository->shouldReceive('update')
            ->once()
            ->withArgs(function (...$args) use ($updateActions) {
                if (count($args) !== count($updateActions)) {
                    return false;
                }
                foreach ($args as $index => $arg) {
                    if (!($arg instanceof EntityOperation)) {
                        return false;
                    }
                }
                return true;
            });

        $this->entityRepository->shouldReceive('delete')
            ->once()
            ->withArgs(function (...$args) use ($deleteActions) {
                if (count($args) !== count($deleteActions)) {
                    return false;
                }
                foreach ($args as $index => $arg) {
                    if (!($arg instanceof EntityOperation)) {
                        return false;
                    }
                }
                return true;
            });

        $this->queueActionRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (...$args) use ($allActions) {
                if (count($args) !== count($allActions)) {
                    return false;
                }
                return true;
            });

        // Setup validation expectations
        $this->entityRepository->shouldReceive('getEntitiesByIds')->andReturn([]);
        $this->entityRepository->shouldReceive('getCount')->andReturn(0);

        // When
        $this->horusQueueActionClient->pushActions(...$allActions);

        // Then - If we reach here without exception, the test passes
        $this->assertTrue(true);
    }

    function testPushActionsWithMaxCountRestrictionExceeded()
    {
        // Given
        $this->expectException(RestrictionException::class);
        $userId = $this->faker->uuid();

        // Use real Config with restrictions
        $customConfig = new Config(
            entityRestrictions: [
                new MaxCountEntityRestriction(
                    $userId,
                    ParentFakeWritableEntity::getEntityName(),
                    1
                )
            ]
        );

        $horusQueueActionClient = new HorusQueueActionClient(
            $this->transactionHandler,
            $this->queueActionRepository,
            $this->entityRepository,
            $customConfig
        );

        // Create test actions
        $insertActions = $this->generateTestActions(EntityInsert::class, 2, $userId);

        // Mock entityRepository to return count exceeding restriction
        $this->entityRepository->shouldReceive('getEntitiesByIds')->andReturn([]);
        $this->entityRepository->shouldReceive('getCount')
            ->andReturn(1); // Current count = 1, trying to add more will exceed restriction

        // When/Then - expect exception
        $horusQueueActionClient->pushActions(...$insertActions);
    }

    function testOrganizeActions()
    {
        // Given
        $insertActions = $this->generateTestActions(EntityInsert::class, 2);
        $updateActions = $this->generateTestActions(EntityUpdate::class, 2);
        $deleteActions = $this->generateTestActions(EntityDelete::class, 2);

        $allActions = array_merge($insertActions, $updateActions, $deleteActions);
        shuffle($allActions);

        // Use reflection to access private method
        $reflection = new \ReflectionClass(HorusQueueActionClient::class);
        $method = $reflection->getMethod('organizeActions');
        $method->setAccessible(true);

        // When
        $result = $method->invokeArgs($this->horusQueueActionClient, $allActions);

        // Then
        $this->assertCount(3, $result);
        $this->assertCount(2, $result[0]); // insertActions
        $this->assertCount(2, $result[1]); // updateActions
        $this->assertCount(2, $result[2]); // deleteActions

        // Verify first array contains only inserts
        foreach ($result[0] as $action) {
            $this->assertInstanceOf(QueueAction::class, $action);
            $this->assertInstanceOf(EntityInsert::class, $action->operation);
        }

        // Verify second array contains only updates
        foreach ($result[1] as $action) {
            $this->assertInstanceOf(QueueAction::class, $action);
            $this->assertInstanceOf(EntityUpdate::class, $action->operation);
        }

        // Verify third array contains only deletes
        foreach ($result[2] as $action) {
            $this->assertInstanceOf(QueueAction::class, $action);
            $this->assertInstanceOf(EntityDelete::class, $action->operation);
        }
    }

    function testGroupEntityByUserOwnerId()
    {
        // Given
        $userId1 = 'user1';
        $userId2 = 'user2';

        $entity1 = new EntityInsert($userId1, 'entity_type', new DateTimeImmutable(), ['id' => 'id1', 'name' => 'Entity 1']);
        $entity2 = new EntityInsert($userId1, 'entity_type', new DateTimeImmutable(), ['id' => 'id2', 'name' => 'Entity 2']);
        $entity3 = new EntityInsert($userId2, 'entity_type', new DateTimeImmutable(), ['id' => 'id3', 'name' => 'Entity 3']);

        // Use reflection to access private method
        $reflection = new \ReflectionClass(HorusQueueActionClient::class);
        $method = $reflection->getMethod('groupEntityByUserOwnerId');
        $method->setAccessible(true);

        // When
        $result = $method->invokeArgs($this->horusQueueActionClient, [$entity1, $entity2, $entity3]);

        // Then
        $this->assertCount(2, $result);
        $this->assertArrayHasKey($userId1, $result);
        $this->assertArrayHasKey($userId2, $result);
        $this->assertCount(2, $result[$userId1]);
        $this->assertCount(1, $result[$userId2]);
    }

    private function generateTestActions(string $entityOperationClass, int $count, string|null $userId = null): array
    {
        $actions = [];
        $entityName = ParentFakeWritableEntity::getEntityName();
        $userId = $userId ?? $this->faker->uuid();

        for ($i = 0; $i < $count; $i++) {

            $entityId = "entity" . $i;
            $actionedAt = new DateTimeImmutable();
            $syncedAt = new DateTimeImmutable();

            $operation = null;

            switch ($entityOperationClass) {
                case EntityInsert::class:
                    $data = ['id' => $entityId, 'name' => "Entity $i"];
                    $operation = new EntityInsert($userId, $entityName, $actionedAt, $data);
                    $action = SyncAction::INSERT;
                    break;

                case EntityUpdate::class:
                    $data = ['name' => "Updated Entity $i"];
                    $operation = new EntityUpdate($userId, $entityName, $entityId, $actionedAt, $data);
                    $action = SyncAction::UPDATE;
                    break;

                case EntityDelete::class:
                    $operation = new EntityDelete($userId, $entityName, $entityId, $actionedAt);
                    $action = SyncAction::DELETE;
                    break;

                default:
                    throw new \InvalidArgumentException("Invalid entity operation class: $entityOperationClass");
            }

            $actions[] = new QueueAction(
                $action,
                $entityName,
                $entityId,
                $operation,
                $actionedAt,
                $syncedAt,
                $userId,
                $userId
            );
        }

        return $actions;
    }

    function testSerializeQueueActionClient()
    {
        $config = new Config(true);
        $config->setupOnValidateEntityWasGranted(function () {
            return true;
        });

        $horusQueueActionClient = new HorusQueueActionClient(
            new EloquentTransactionHandler(),
            $this->app->make(EloquentQueueActionRepository::class),
            $this->app->make(EloquentEntityRepository::class),
            $config
        );

        $serialized = serialize(clone $horusQueueActionClient);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(HorusQueueActionClient::class, $unserialized);
    }
}
