<?php

namespace Tests\Unit\Repository;


use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use AppTank\Horus\Repository\EloquentEntityRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ChildFakeEntity;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ParentFakeEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\TestCase;

class EloquentEntityRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentEntityRepository $entityRepository;

    protected bool $initializeContainer = false;

    function setUp(): void
    {
        HorusContainer::initialize([
            ParentFakeEntity::class => [
                ChildFakeEntity::class
            ]
        ]);

        parent::setUp();

        $this->entityRepository = new EloquentEntityRepository(
            HorusContainer::getInstance()->getEntityMapper(),
            new DateTimeUtil()
        );
    }

    function testInsertIsSuccess()
    {
        // Given
        /**
         * @var EntityOperation[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ParentFakeEntity::getEntityName(), ParentFakeEntityFactory::newData(), now()->toDateTimeImmutable()
        ));
        /**
         * @var EntityOperation[] $childEntities
         */
        $childEntities = $this->generateArray(fn() => EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ChildFakeEntity::getEntityName(), ChildFakeEntityFactory::newData(), now()->toDateTimeImmutable()
        ));
        $operations = array_merge($parentsEntities, $childEntities);
        shuffle($operations);

        // When
        $this->entityRepository->insert(...$operations);

        // Then
        $this->assertDatabaseCount(ParentFakeEntity::getTableName(), count($parentsEntities));
        $this->assertDatabaseCount(ChildFakeEntity::getTableName(), count($childEntities));

        foreach ($parentsEntities as $entity) {
            $expectedData = $entity->toArray();
            $expectedData[EntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($expectedData);
            $this->assertDatabaseHas(ParentFakeEntity::getTableName(), $expectedData);
        }

        foreach ($childEntities as $entity) {
            $expectedData = $entity->toArray();
            $expectedData[EntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($expectedData);
            $this->assertDatabaseHas(ChildFakeEntity::getTableName(), $expectedData);
        }
    }

    function testUpdateMultiplesRowsIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $parentsEntities = $this->generateCountArray(fn() => ParentFakeEntityFactory::create());
        /**
         * @var EntityUpdate[] $updateOperations
         */
        $updateOperations = array_map(function (ParentFakeEntity $entity) use ($ownerId) {
            $entityName = ParentFakeEntity::getEntityName();
            $attributes = [ParentFakeEntity::ATTR_COLOR => $this->faker->colorName];
            return EntityOperationFactory::createEntityUpdate($ownerId, $entityName, $entity->getId(), $attributes, now()->toDateTimeImmutable());
        }, $parentsEntities);

        // When
        $this->entityRepository->update(...$updateOperations);

        // Then
        foreach ($updateOperations as $index => $operation) {

            $expectedData = array_merge(
                ["id" => $operation->id, ParentFakeEntity::ATTR_NAME => $parentsEntities[$index]->name],
                $operation->attributes
            );
            $hashExpected = Hasher::hash($expectedData);
            $expectedData[EntitySynchronizable::ATTR_SYNC_HASH] = $hashExpected;
            $this->assertDatabaseHas(ParentFakeEntity::getTableName(), $expectedData);
        }
    }

    function testUpdateSameRowIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $parentEntity = ParentFakeEntityFactory::create();

        $updateOperations = $this->generateCountArray(fn() => EntityOperationFactory::createEntityUpdate(
            $ownerId,
            ParentFakeEntity::getEntityName(),
            $parentEntity->getId(),
            [ParentFakeEntity::ATTR_COLOR => $this->faker->colorName],
            \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween())
        ), 10);


        // When
        $this->entityRepository->update(...$updateOperations);

        // Then

        // Sort operations by actionedAt
        usort($updateOperations, fn(EntityUpdate $a, EntityUpdate $b) => $a->actionedAt <=> $b->actionedAt);
        $lastOperation = end($updateOperations);
        $expectedData = array_merge(
            ["id" => $lastOperation->id, ParentFakeEntity::ATTR_NAME => $parentEntity->name],
            $lastOperation->attributes
        );
        $hashExpected = Hasher::hash($expectedData);
        $expectedData[EntitySynchronizable::ATTR_SYNC_HASH] = $hashExpected;
        $this->assertDatabaseHas(ParentFakeEntity::getTableName(), $expectedData);
    }

    function testUpdateRowSoftDeletedIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $parentEntity = ParentFakeEntityFactory::create();
        $parentEntity->deleteOrFail();

        $updateOperation = EntityOperationFactory::createEntityUpdate(
            $ownerId,
            ParentFakeEntity::getEntityName(),
            $parentEntity->getId(),
            [ParentFakeEntity::ATTR_COLOR => $this->faker->colorName],
            \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween())
        );

        // When
        $this->entityRepository->update($updateOperation);

        // Then
        $expectedData = array_merge(
            ["id" => $updateOperation->id, ParentFakeEntity::ATTR_NAME => $parentEntity->name],
            $updateOperation->attributes
        );
        $this->assertSoftDeleted(ParentFakeEntity::getTableName(), $expectedData, deletedAtColumn: EntitySynchronizable::ATTR_SYNC_DELETED_AT);
        $this->assertDatabaseHas(ParentFakeEntity::getTableName(), $expectedData);
    }

}
