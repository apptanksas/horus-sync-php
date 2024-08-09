<?php

namespace Tests\Unit\Repository;


use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use AppTank\Horus\Repository\EloquentEntityRepository;
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

    function testInsertWithNullableIsSuccess()
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

    function testInsertWithoutNullableIsSuccess()
    {
        // Given
        /**
         * @var EntityOperation[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ParentFakeEntity::getEntityName(), ParentFakeEntityFactory::newData($this->faker->name), now()->toDateTimeImmutable()
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

            if (!is_null($parentsEntities[$index]->{ParentFakeEntity::ATTR_VALUE_NULLABLE})) {
                $expectedData[ParentFakeEntity::ATTR_VALUE_NULLABLE] = $parentsEntities[$index]->{ParentFakeEntity::ATTR_VALUE_NULLABLE};
            }

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

    function testDeleteIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $parentEntity = ParentFakeEntityFactory::create($ownerId);

        $deleteOperation = EntityOperationFactory::createEntityDelete(
            $ownerId,
            ParentFakeEntity::getEntityName(),
            $parentEntity->getId(),
            \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween())
        );

        // When
        $this->entityRepository->delete($deleteOperation);

        // Then
        $expectedData = ["id" => $deleteOperation->id];
        $this->assertSoftDeleted(ParentFakeEntity::getTableName(),
            $expectedData,
            deletedAtColumn: EntitySynchronizable::ATTR_SYNC_DELETED_AT);
    }

    function testDeleteManyEntitiesIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        /**
         * @var ParentFakeEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        $childEntities = [];

        foreach ($parentsEntities as $parentEntity) {
            $childEntities = array_merge($childEntities, $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $ownerId)));
        }

        $deleteOperations = array_map(function (ParentFakeEntity $entity) use ($ownerId) {
            return EntityOperationFactory::createEntityDelete(
                $ownerId,
                ParentFakeEntity::getEntityName(),
                $entity->getId(),
                \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween())
            );
        }, $parentsEntities);

        // When
        $this->entityRepository->delete(...$deleteOperations);

        // Then

        // Validate parent entities are soft deleted
        foreach ($parentsEntities as $parentFakeEntity) {
            $expectedData = ["id" => $parentFakeEntity->getId()];
            $this->assertSoftDeleted(ParentFakeEntity::getTableName(),
                $expectedData,
                deletedAtColumn: EntitySynchronizable::ATTR_SYNC_DELETED_AT);
        }
        // Validate child entities are soft deleted
        foreach ($childEntities as $childFakeEntity) {
            $expectedData = ["id" => $childFakeEntity->getId()];
            $this->assertSoftDeleted(ChildFakeEntity::getTableName(),
                $expectedData,
                deletedAtColumn: EntitySynchronizable::ATTR_SYNC_DELETED_AT);
        }
    }

    function testSearchAllEntitiesByUserIdIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        /**
         * @var ParentFakeEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        $childEntities = [];

        foreach ($parentsEntities as $parentEntity) {
            $childEntities[$parentEntity->getId()] = $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $ownerId));
        }

        // When
        /**
         * @var $result EntityData[]
         */
        $result = $this->entityRepository->searchAllEntitiesByUserId($ownerId);

        // Then
        $this->assertCount(count($parentsEntities), $result);

        foreach ($childEntities as $parentId => $childs) {
            $children = array_merge([], array_filter($result, fn(EntityData $entity) => $entity->getData()["id"] === $parentId))[0]->getData()["_children"];
            $this->assertCount(count($childEntities[$parentId]), $children);
        }

        foreach ($parentsEntities as $parentEntity) {
            $parentEntityResult = array_merge([], array_filter($result, fn(EntityData $entity) => $entity->getData()["id"] === $parentEntity->getId()))[0];

            $this->assertEquals($parentEntity->getId(), $parentEntityResult->getData()["id"]);
            $this->assertEquals($parentEntity->name, $parentEntityResult->getData()[ParentFakeEntity::ATTR_NAME]);
            $this->assertEquals($parentEntity->color, $parentEntityResult->getData()[ParentFakeEntity::ATTR_COLOR]);

            $children = $parentEntityResult->getData()["_children"];

            foreach ($childEntities[$parentEntity->getId()] as $childEntity) {
                $childEntityResult = array_merge([], array_filter($children, fn($entity) => $entity->getData()["id"] == $childEntity->getId()))[0]->getData();
                $this->assertEquals($childEntity->getId(), $childEntityResult[ChildFakeEntity::ATTR_ID]);
                $this->assertEquals($childEntity->{ChildFakeEntity::ATTR_BOOLEAN_VALUE}, $childEntityResult[ChildFakeEntity::ATTR_BOOLEAN_VALUE]);
                $this->assertEquals($childEntity->{ChildFakeEntity::ATTR_INT_VALUE}, $childEntityResult[ChildFakeEntity::ATTR_INT_VALUE]);
                $this->assertEquals(round(floatval($childEntity->{ChildFakeEntity::ATTR_FLOAT_VALUE}), 2), round(floatval($childEntityResult[ChildFakeEntity::ATTR_FLOAT_VALUE]), 2), 2);
                $this->assertEquals($childEntity->{ChildFakeEntity::ATTR_STRING_VALUE}, $childEntityResult[ChildFakeEntity::ATTR_STRING_VALUE]);
                $this->assertEquals($childEntity->{ChildFakeEntity::ATTR_TIMESTAMP_VALUE}, $childEntityResult[ChildFakeEntity::ATTR_TIMESTAMP_VALUE]);
                $this->assertEquals($childEntity->{ChildFakeEntity::ATTR_PRIMARY_INT_VALUE}, $childEntityResult[ChildFakeEntity::ATTR_PRIMARY_INT_VALUE]);
                $this->assertEquals($childEntity->{ChildFakeEntity::ATTR_PRIMARY_STRING_VALUE}, $childEntityResult[ChildFakeEntity::ATTR_PRIMARY_STRING_VALUE]);
                $this->assertEquals($childEntity->{ChildFakeEntity::FK_PARENT_ID}, $childEntityResult[ChildFakeEntity::FK_PARENT_ID]);
                $this->assertEquals($childEntity->{ChildFakeEntity::ATTR_SYNC_HASH}, $childEntityResult[ChildFakeEntity::ATTR_SYNC_HASH]);
                $this->assertEquals($childEntity->{ChildFakeEntity::ATTR_SYNC_OWNER_ID}, $childEntityResult[ChildFakeEntity::ATTR_SYNC_OWNER_ID]);
                $this->assertEquals($childEntity->{ChildFakeEntity::ATTR_SYNC_CREATED_AT}, $childEntityResult[ChildFakeEntity::ATTR_SYNC_CREATED_AT]);
                $this->assertEquals($childEntity->{ChildFakeEntity::ATTR_SYNC_UPDATED_AT}, $childEntityResult[ChildFakeEntity::ATTR_SYNC_UPDATED_AT]);

                $this->assertIsInt($childEntityResult[ChildFakeEntity::ATTR_SYNC_UPDATED_AT]);
                $this->assertIsInt($childEntityResult[ChildFakeEntity::ATTR_SYNC_CREATED_AT]);
                $this->assertTrue($childEntityResult[ChildFakeEntity::ATTR_SYNC_UPDATED_AT] > 0);
                $this->assertTrue($childEntityResult[ChildFakeEntity::ATTR_SYNC_CREATED_AT] > 0);
            }
        }
    }

    function testSearchAllEntitiesWithoutSoftDeletedIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        /**
         * @var ParentFakeEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        //Parents entities soft deleted
        $parentsEntitiesDeleted = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        foreach ($parentsEntitiesDeleted as $parentEntity) {
            $parentEntity->delete();
        }

        // When
        /**
         * @var $result EntityData[]
         */
        $result = $this->entityRepository->searchAllEntitiesByUserId($ownerId);

        // Then
        $this->assertCount(count($parentsEntities), $result);
        // Validate soft deleted entities and entities not soft deleted are in the database
        $this->assertDatabaseCount(ParentFakeEntity::getTableName(), count($parentsEntities) + count($parentsEntitiesDeleted));
    }

    function testSearchEntitiesAfterUpdatedAtIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $updatedAt = $this->faker->dateTimeBetween()->getTimestamp();
        /**
         * @var ParentFakeEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId, [
            EntitySynchronizable::ATTR_SYNC_UPDATED_AT => $updatedAt
        ]));

        // Generate entities before the updatedAt
        $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId, [
            EntitySynchronizable::ATTR_SYNC_UPDATED_AT => $this->faker->dateTimeBetween(endDate: $updatedAt)->getTimestamp()
        ]));

        $updatedAtTarget = $updatedAt - 1;
        $countExpected = count(array_filter($parentsEntities, fn(ParentFakeEntity $entity) => $entity->getUpdatedAt() > $updatedAtTarget));

        // When
        $result = $this->entityRepository->searchEntitiesAfterUpdatedAt($ownerId, $updatedAtTarget);

        // Then
        $this->assertCount($countExpected, $result);
    }

    function testSearchEntitiesDefaultIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        /**
         * @var ParentFakeEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        // Others records
        $this->generateArray(fn() => ParentFakeEntityFactory::create());

        // When
        $result = $this->entityRepository->searchEntities($ownerId, ParentFakeEntity::getEntityName());

        // Then
        $this->assertCount(count($parentsEntities), $result);
    }

    function testSearchEntitiesByIdsIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        /**
         * @var ParentFakeEntity[] $parentsEntities
         */
        $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        $parentsEntitiesToSearch = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        $idsExpected = array_map(fn(ParentFakeEntity $entity) => $entity->getId(), $parentsEntitiesToSearch);

        // When
        $result = $this->entityRepository->searchEntities($ownerId, ParentFakeEntity::getEntityName(), $idsExpected);

        // Then
        $this->assertCount(count($idsExpected), $result);

        foreach ($result as $entityData) {
            $this->assertTrue(in_array($entityData->getData()["id"], $idsExpected));
        }
    }

    function testSearchEntitiesByTimestampIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        $timestamp = $this->faker->dateTimeBetween()->getTimestamp();
        /**
         * @var ParentFakeEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId, [
            EntitySynchronizable::ATTR_SYNC_UPDATED_AT => $timestamp
        ]));

        // Generate entities before the updatedAt
        $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId, [
            EntitySynchronizable::ATTR_SYNC_UPDATED_AT => $this->faker->dateTimeBetween(endDate: $timestamp)->getTimestamp()
        ]));

        // When
        $result = $this->entityRepository->searchEntities($ownerId, ParentFakeEntity::getEntityName(), [], $timestamp - 1);

        // Then
        $this->assertCount(count($parentsEntities), $result);
    }


}
