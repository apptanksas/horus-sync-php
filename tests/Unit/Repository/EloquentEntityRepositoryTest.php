<?php

namespace Tests\Unit\Repository;


use AppTank\Horus\Core\Config\Restriction\FilterEntityRestriction;
use AppTank\Horus\Core\Config\Restriction\valueObject\ParameterFilter;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Exception\OperationNotPermittedException;
use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Repository\CacheRepository;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use AppTank\Horus\Repository\EloquentEntityRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery\Mock;
use Tests\_Stubs\AdjacentFakeWritableEntity;
use Tests\_Stubs\AdjacentFakeEntityFactory;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ReadableFakeEntity;
use Tests\_Stubs\ReadableFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\TestCase;

class EloquentEntityRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentEntityRepository $entityRepository;

    private CacheRepository|Mock $cacheRepository;

    function setUp(): void
    {
        Horus::initialize([
            ParentFakeWritableEntity::class => [
                ChildFakeWritableEntity::class
            ]
        ]);

        parent::setUp();

        $horus = Horus::getInstance();

        $this->cacheRepository = $this->mock(CacheRepository::class);

        $this->entityRepository = new EloquentEntityRepository(
            $horus->getEntityMapper(),
            $this->cacheRepository,
            new DateTimeUtil(),
            $horus->getConfig()
        );
    }

    function testInsertWithNullableIsSuccess()
    {
        // Given
        ChildFakeWritableEntity::query()->forceDelete();
        ParentFakeWritableEntity::query()->forceDelete();

        /**
         * @var EntityOperation[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ParentFakeWritableEntity::getEntityName(), ParentFakeEntityFactory::newData(), now()->toDateTimeImmutable()
        ));
        /**
         * @var EntityOperation[] $childEntities
         */
        $childEntities = $this->generateArray(fn() => EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ChildFakeWritableEntity::getEntityName(), ChildFakeEntityFactory::newData(ParentFakeEntityFactory::create()->getId()), now()->toDateTimeImmutable()
        ));
        $operations = array_merge($parentsEntities, $childEntities);
        shuffle($operations);

        // When
        $this->entityRepository->insert(...$operations);

        // Then
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), count($parentsEntities) + count($childEntities));
        $this->assertDatabaseCount(ChildFakeWritableEntity::getTableName(), count($childEntities));

        foreach ($parentsEntities as $entity) {
            $expectedData = $entity->toArray();
            $expectedData[WritableEntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($expectedData);
            $expectedData[ParentFakeWritableEntity::ATTR_TIMESTAMP] = $this->getDateTimeUtil()->getFormatDate($expectedData[ParentFakeWritableEntity::ATTR_TIMESTAMP]);
            $this->assertDatabaseHas(ParentFakeWritableEntity::getTableName(), $expectedData);
        }

        foreach ($childEntities as $entity) {
            $expectedData = $entity->toArray();
            $expectedData[WritableEntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($expectedData);
            $expectedData[ChildFakeWritableEntity::ATTR_TIMESTAMP_VALUE] = $this->getDateTimeUtil()->getFormatDate($expectedData[ChildFakeWritableEntity::ATTR_TIMESTAMP_VALUE]);
            $this->assertDatabaseHas(ChildFakeWritableEntity::getTableName(), $expectedData);
        }
    }

    function testInsertWithoutNullableIsSuccess()
    {
        // Given
        Schema::disableForeignKeyConstraints();
        /**
         * @var EntityOperation[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ParentFakeWritableEntity::getEntityName(), ParentFakeEntityFactory::newData($this->faker->name), now()->toDateTimeImmutable()
        ));
        /**
         * @var EntityOperation[] $childEntities
         */
        $childEntities = $this->generateArray(fn() => EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ChildFakeWritableEntity::getEntityName(), ChildFakeEntityFactory::newData(ParentFakeEntityFactory::create()->getId()), now()->toDateTimeImmutable()
        ));
        $operations = array_merge($parentsEntities, $childEntities);
        shuffle($operations);

        // When
        $this->entityRepository->insert(...$operations);

        // Then
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), count($parentsEntities) + count($childEntities));
        $this->assertDatabaseCount(ChildFakeWritableEntity::getTableName(), count($childEntities));

        foreach ($parentsEntities as $entity) {
            $expectedData = $entity->toArray();
            $expectedData[WritableEntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($expectedData);
            $expectedData[ParentFakeWritableEntity::ATTR_TIMESTAMP] = $this->getDateTimeUtil()->getFormatDate($expectedData[ParentFakeWritableEntity::ATTR_TIMESTAMP]);
            $this->assertDatabaseHas(ParentFakeWritableEntity::getTableName(), $expectedData);
        }

        foreach ($childEntities as $entity) {
            $expectedData = $entity->toArray();
            $expectedData[WritableEntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($expectedData);
            $expectedData[ChildFakeWritableEntity::ATTR_TIMESTAMP_VALUE] = $this->getDateTimeUtil()->getFormatDate($expectedData[ChildFakeWritableEntity::ATTR_TIMESTAMP_VALUE]);
            $this->assertDatabaseHas(ChildFakeWritableEntity::getTableName(), $expectedData);
        }
    }

    function testUpdateMultiplesRowsIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $parentsEntities = $this->generateCountArray(fn() => ParentFakeEntityFactory::create());
        /**
         * @var EntityUpdate[] $updateOperations
         */
        $updateOperations = array_map(function (ParentFakeWritableEntity $entity) use ($ownerId) {
            $entityName = ParentFakeWritableEntity::getEntityName();
            $attributes = [
                ParentFakeWritableEntity::ATTR_COLOR => $this->faker->colorName,
                ParentFakeWritableEntity::ATTR_TIMESTAMP => $this->faker->dateTimeBetween()->getTimestamp(),
            ];
            return EntityOperationFactory::createEntityUpdate($ownerId, $entityName, $entity->getId(), $attributes, now()->toDateTimeImmutable());
        }, $parentsEntities);

        // When
        $this->entityRepository->update(...$updateOperations);

        // Then
        foreach ($updateOperations as $index => $operation) {

            $expectedData = array_merge(
                ["id" => $operation->id,
                    ParentFakeWritableEntity::ATTR_NAME => $parentsEntities[$index]->name,
                    ParentFakeWritableEntity::ATTR_ENUM => $parentsEntities[$index]->value_enum,
                    ParentFakeWritableEntity::ATTR_IMAGE => $parentsEntities[$index]->image
                ],
                $operation->attributes
            );

            if (!is_null($parentsEntities[$index]->{ParentFakeWritableEntity::ATTR_VALUE_NULLABLE})) {
                $expectedData[ParentFakeWritableEntity::ATTR_VALUE_NULLABLE] = $parentsEntities[$index]->{ParentFakeWritableEntity::ATTR_VALUE_NULLABLE};
            }

            $hashExpected = Hasher::hash($expectedData);
            $expectedData[WritableEntitySynchronizable::ATTR_SYNC_HASH] = $hashExpected;
            $expectedData[ParentFakeWritableEntity::ATTR_TIMESTAMP] = $this->getDateTimeUtil()->getFormatDate($expectedData[ParentFakeWritableEntity::ATTR_TIMESTAMP]);

            $this->assertDatabaseHas(ParentFakeWritableEntity::getTableName(), $expectedData);
        }
    }

    function testUpdateSameRowIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $parentEntity = ParentFakeEntityFactory::create();

        $updateOperations = $this->generateCountArray(fn() => EntityOperationFactory::createEntityUpdate(
            $ownerId,
            ParentFakeWritableEntity::getEntityName(),
            $parentEntity->getId(),
            [ParentFakeWritableEntity::ATTR_COLOR => $this->faker->colorName, ParentFakeWritableEntity::ATTR_TIMESTAMP => $this->faker->dateTimeBetween()->getTimestamp()],
            \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween())
        ), 10);


        // When
        $this->entityRepository->update(...$updateOperations);

        // Then

        // Sort operations by actionedAt
        usort($updateOperations, fn(EntityUpdate $a, EntityUpdate $b) => $a->actionedAt <=> $b->actionedAt);
        $lastOperation = end($updateOperations);
        $expectedData = array_merge(
            ["id" => $lastOperation->id,
                ParentFakeWritableEntity::ATTR_NAME => $parentEntity->name,
                ParentFakeWritableEntity::ATTR_ENUM => $parentEntity->value_enum,
                ParentFakeWritableEntity::ATTR_IMAGE => $parentEntity->image
            ],
            $lastOperation->attributes
        );

        if (!is_null($parentEntity->{ParentFakeWritableEntity::ATTR_VALUE_NULLABLE})) {
            $expectedData[ParentFakeWritableEntity::ATTR_VALUE_NULLABLE] = $parentEntity->{ParentFakeWritableEntity::ATTR_VALUE_NULLABLE};
        }

        $hashExpected = Hasher::hash($expectedData);
        $expectedData[WritableEntitySynchronizable::ATTR_SYNC_HASH] = $hashExpected;
        // As the column timestamp is a timestamp, we need to convert it to a date
        $expectedData[ParentFakeWritableEntity::ATTR_TIMESTAMP] = $this->getDateTimeUtil()->getFormatDate($expectedData[ParentFakeWritableEntity::ATTR_TIMESTAMP]);

        $this->assertDatabaseHas(ParentFakeWritableEntity::getTableName(), $expectedData);
    }

    function testUpdateRowSoftDeletedIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $parentEntity = ParentFakeEntityFactory::create();
        $parentEntity->deleteOrFail();

        $updateOperation = EntityOperationFactory::createEntityUpdate(
            $ownerId,
            ParentFakeWritableEntity::getEntityName(),
            $parentEntity->getId(),
            [ParentFakeWritableEntity::ATTR_COLOR => $this->faker->colorName],
            \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween())
        );

        // When
        $this->entityRepository->update($updateOperation);

        // Then
        $expectedData = array_merge(
            ["id" => $updateOperation->id, ParentFakeWritableEntity::ATTR_NAME => $parentEntity->name],
            $updateOperation->attributes
        );
        $this->assertSoftDeleted(ParentFakeWritableEntity::getTableName(), $expectedData, deletedAtColumn: WritableEntitySynchronizable::ATTR_SYNC_DELETED_AT);
        $this->assertDatabaseHas(ParentFakeWritableEntity::getTableName(), $expectedData);
    }

    function testDeleteIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $parentEntity = ParentFakeEntityFactory::create($ownerId);

        $deleteOperation = EntityOperationFactory::createEntityDelete(
            $ownerId,
            ParentFakeWritableEntity::getEntityName(),
            $parentEntity->getId(),
            \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween())
        );

        // When
        $this->entityRepository->delete($deleteOperation);

        // Then
        $expectedData = ["id" => $deleteOperation->id];
        $this->assertSoftDeleted(ParentFakeWritableEntity::getTableName(),
            $expectedData,
            deletedAtColumn: WritableEntitySynchronizable::ATTR_SYNC_DELETED_AT);
    }

    function testSoftDeleteEntityParentAndSoftDeleteOnCascadeChildrenIsSuccess()
    {
        // Given
        Schema::disableForeignKeyConstraints();
        $ownerId = $this->faker->uuid;
        /**
         * @var ParentFakeWritableEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        $childEntities = [];

        foreach ($parentsEntities as $parentEntity) {
            $childEntities = array_merge($childEntities, $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $ownerId)));
        }

        $deleteOperations = array_map(function (ParentFakeWritableEntity $entity) use ($ownerId) {
            return EntityOperationFactory::createEntityDelete(
                $ownerId,
                ParentFakeWritableEntity::getEntityName(),
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
            $this->assertSoftDeleted(ParentFakeWritableEntity::getTableName(),
                $expectedData,
                deletedAtColumn: WritableEntitySynchronizable::ATTR_SYNC_DELETED_AT);
        }
        // Validate child entities are soft deleted
        foreach ($childEntities as $childFakeEntity) {
            $expectedData = ["id" => $childFakeEntity->getId()];
            $this->assertSoftDeleted(ChildFakeWritableEntity::getTableName(),
                $expectedData,
                deletedAtColumn: WritableEntitySynchronizable::ATTR_SYNC_DELETED_AT);
        }
    }

    function testSearchAllEntitiesByUserIdIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        /**
         * @var ParentFakeWritableEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        $childEntities = [];
        /**
         * @var AdjacentFakeWritableEntity[] $adjacentEntities
         */
        $adjacentEntities = [];

        foreach ($parentsEntities as $parentEntity) {
            $childEntities[$parentEntity->getId()] = $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $ownerId));
            $adjacentEntities[$parentEntity->getId()] = AdjacentFakeEntityFactory::create($parentEntity->getId(), $ownerId);
        }

        $this->cacheRepository->shouldReceive("exists")->andReturn(false);
        $this->cacheRepository->shouldReceive("set")->once();

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
            $this->assertEquals($parentEntity->name, $parentEntityResult->getData()[ParentFakeWritableEntity::ATTR_NAME]);
            $this->assertEquals($parentEntity->color, $parentEntityResult->getData()[ParentFakeWritableEntity::ATTR_COLOR]);

            $children = $parentEntityResult->getData()["_children"];
            /**
             * @var EntityData $adjacentResult
             */
            $adjacentResult = $parentEntityResult->getData()["_adjacent"];

            foreach ($childEntities[$parentEntity->getId()] as $childEntity) {
                $childEntityResult = array_merge([], array_filter($children, fn($entity) => $entity->getData()["id"] == $childEntity->getId()))[0]->getData();
                $this->assertEquals($childEntity->getId(), $childEntityResult[ChildFakeWritableEntity::ATTR_ID]);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::ATTR_BOOLEAN_VALUE}, $childEntityResult[ChildFakeWritableEntity::ATTR_BOOLEAN_VALUE]);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::ATTR_INT_VALUE}, $childEntityResult[ChildFakeWritableEntity::ATTR_INT_VALUE]);
                $this->assertEquals(round(floatval($childEntity->{ChildFakeWritableEntity::ATTR_FLOAT_VALUE}), 2), round(floatval($childEntityResult[ChildFakeWritableEntity::ATTR_FLOAT_VALUE]), 2), 2);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::ATTR_STRING_VALUE}, $childEntityResult[ChildFakeWritableEntity::ATTR_STRING_VALUE]);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::ATTR_TIMESTAMP_VALUE}, $childEntityResult[ChildFakeWritableEntity::ATTR_TIMESTAMP_VALUE]);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::ATTR_PRIMARY_INT_VALUE}, $childEntityResult[ChildFakeWritableEntity::ATTR_PRIMARY_INT_VALUE]);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::ATTR_PRIMARY_STRING_VALUE}, $childEntityResult[ChildFakeWritableEntity::ATTR_PRIMARY_STRING_VALUE]);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::FK_PARENT_ID}, $childEntityResult[ChildFakeWritableEntity::FK_PARENT_ID]);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::ATTR_SYNC_HASH}, $childEntityResult[ChildFakeWritableEntity::ATTR_SYNC_HASH]);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::ATTR_SYNC_OWNER_ID}, $childEntityResult[ChildFakeWritableEntity::ATTR_SYNC_OWNER_ID]);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::ATTR_SYNC_CREATED_AT}, $childEntityResult[ChildFakeWritableEntity::ATTR_SYNC_CREATED_AT]);
                $this->assertEquals($childEntity->{ChildFakeWritableEntity::ATTR_SYNC_UPDATED_AT}, $childEntityResult[ChildFakeWritableEntity::ATTR_SYNC_UPDATED_AT]);

                $this->assertIsInt($childEntityResult[ChildFakeWritableEntity::ATTR_SYNC_UPDATED_AT]);
                $this->assertIsInt($childEntityResult[ChildFakeWritableEntity::ATTR_SYNC_CREATED_AT]);
                $this->assertTrue($childEntityResult[ChildFakeWritableEntity::ATTR_SYNC_UPDATED_AT] > 0);
                $this->assertTrue($childEntityResult[ChildFakeWritableEntity::ATTR_SYNC_CREATED_AT] > 0);
            }

            // Adjacent
            $this->assertEquals($adjacentEntities[$parentEntity->getId()]->name, $adjacentResult->getData()["name"]);
            $this->assertEquals($adjacentEntities[$parentEntity->getId()]->{WritableEntitySynchronizable::ATTR_SYNC_HASH}, $adjacentResult->getData()[WritableEntitySynchronizable::ATTR_SYNC_HASH]);
            $this->assertEquals($adjacentEntities[$parentEntity->getId()]->{WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID}, $adjacentResult->getData()[WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID]);
            $this->assertEquals($adjacentEntities[$parentEntity->getId()]->{WritableEntitySynchronizable::ATTR_SYNC_CREATED_AT}, $adjacentResult->getData()[WritableEntitySynchronizable::ATTR_SYNC_CREATED_AT]);
        }
    }

    function testSearchAllEntitiesWithoutSoftDeletedIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        /**
         * @var ParentFakeWritableEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        //Parents entities soft deleted
        $parentsEntitiesDeleted = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        foreach ($parentsEntitiesDeleted as $parentEntity) {
            $parentEntity->delete();
        }

        $this->cacheRepository->shouldReceive("exists")->andReturn(false);
        $this->cacheRepository->shouldReceive("set")->once();

        // When
        /**
         * @var $result EntityData[]
         */
        $result = $this->entityRepository->searchAllEntitiesByUserId($ownerId);

        // Then
        $this->assertCount(count($parentsEntities), $result);
        // Validate soft deleted entities and entities not soft deleted are in the database
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), count($parentsEntities) + count($parentsEntitiesDeleted));
    }

    function testSearchEntitiesAfterUpdatedAtIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        $updatedAt = $this->faker->dateTimeBetween()->getTimestamp();
        /**
         * @var ParentFakeWritableEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId, [
            WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT => $this->getDateTimeUtil()->getFormatDate($updatedAt)
        ]));

        // Generate entities before the updatedAt
        $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId, [
            WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT => $this->faker->dateTimeBetween(endDate: $updatedAt)->getTimestamp()
        ]));

        $updatedAtTarget = $updatedAt - 1;
        $countExpected = count(array_filter($parentsEntities, fn(ParentFakeWritableEntity $entity) => $entity->getUpdatedAt()->getTimestamp() > $updatedAtTarget));

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
         * @var ParentFakeWritableEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        // Others records
        $this->generateArray(fn() => ParentFakeEntityFactory::create());

        $this->cacheRepository->shouldReceive("exists")->andReturn(false);

        // When
        $result = $this->entityRepository->searchEntities($ownerId, ParentFakeWritableEntity::getEntityName());

        // Then
        $this->assertCount(count($parentsEntities), $result);
    }

    function testSearchEntitiesByIdsIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        /**
         * @var ParentFakeWritableEntity[] $parentsEntities
         */
        $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        $parentsEntitiesToSearch = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));
        $idsExpected = array_map(fn(ParentFakeWritableEntity $entity) => $entity->getId(), $parentsEntitiesToSearch);

        $this->cacheRepository->shouldReceive("exists")->andReturn(false);

        // When
        $result = $this->entityRepository->searchEntities($ownerId, ParentFakeWritableEntity::getEntityName(), $idsExpected);

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
         * @var ParentFakeWritableEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId, [
            WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT => $this->getDateTimeUtil()->getFormatDate($timestamp)
        ]));

        // Generate entities before the updatedAt
        $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId, [
            WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT => $this->getDateTimeUtil()->getFormatDate($this->faker->dateTimeBetween(endDate: $timestamp)->getTimestamp())
        ]));

        $this->cacheRepository->shouldReceive("exists")->andReturn(false);

        // When
        $result = $this->entityRepository->searchEntities($ownerId, ParentFakeWritableEntity::getEntityName(), [], $timestamp - 1);

        // Then
        $this->assertCount(count($parentsEntities), $result);
    }

    function testEntityHashesIsSuccess()
    {
        // Given
        $ownerId = $this->faker->uuid;
        /**
         * @var ParentFakeWritableEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId));

        // When
        $result = $this->entityRepository->getEntityHashes($ownerId, ParentFakeWritableEntity::getEntityName());

        // Then
        $this->assertCount(count($parentsEntities), $result);

        foreach ($parentsEntities as $parentEntity) {
            $this->assertNotEmpty(array_filter($result, fn($item) => $item[WritableEntitySynchronizable::ATTR_ID] == $parentEntity->getId()));
            $this->assertNotEmpty(array_filter($result, fn($item) => $item[WritableEntitySynchronizable::ATTR_SYNC_HASH] == $parentEntity->getHash()));
        }
    }

    function testInsertLookupEntityIsFailureByLookupEntity()
    {
        $this->expectException(OperationNotPermittedException::class);

        // Given
        $operation = EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ReadableFakeEntity::getEntityName(), ReadableFakeEntityFactory::newData(), now()->toDateTimeImmutable()
        );

        // When
        $this->entityRepository->insert($operation);
    }

    function testUpdateLookupEntityIsFailureByLookupEntity()
    {
        $this->expectException(OperationNotPermittedException::class);

        // Given
        $lookup = ReadableFakeEntityFactory::create();
        $operation = EntityOperationFactory::createEntityUpdate(
            $lookup->getId(),
            ReadableFakeEntity::getEntityName(),
            $this->faker->uuid,
            ReadableFakeEntityFactory::newData(), now()->toDateTimeImmutable()
        );

        // When
        $this->entityRepository->update($operation);
    }

    function testDeleteLookupEntityIsFailureByLookupEntity()
    {
        $this->expectException(OperationNotPermittedException::class);

        // Given
        $lookup = ReadableFakeEntityFactory::create();
        $operation = EntityOperationFactory::createEntityDelete(
            $lookup->getId(),
            ReadableFakeEntity::getEntityName(),
            $this->faker->uuid,
            now()->toDateTimeImmutable()
        );

        // When
        $this->entityRepository->delete($operation);
    }

    function testSearchEntitiesReturnLookup()
    {
        // Given
        $ownerId = $this->faker->uuid;
        $entities = $this->generateArray(fn() => ReadableFakeEntityFactory::create());

        $this->cacheRepository->shouldReceive("exists")->andReturn(false);
        $this->cacheRepository->shouldReceive("set")->once();

        // When
        $lookupEntity = $this->entityRepository->searchEntities($ownerId, ReadableFakeEntity::getEntityName());

        // Then
        $this->assertCount(count($entities), $lookupEntity);
    }


    function testSearchEntitiesReturnLookupWithIds()
    {
        // Given
        $ownerId = $this->faker->uuid;
        $entities = $this->generateArray(fn() => ReadableFakeEntityFactory::create());
        $ids = array_map(fn(ReadableFakeEntity $entity) => $entity->getId(), $entities);
        $this->generateArray(fn() => ReadableFakeEntityFactory::create());

        // When
        $lookupEntity = $this->entityRepository->searchEntities($ownerId, ReadableFakeEntity::getEntityName(), $ids);

        // Then
        $this->assertCount(count($entities), $lookupEntity);
    }

    function testShouldEntityExistIsTrue()
    {
        // Given
        $ownerId = $this->faker->uuid;
        $entity = ParentFakeEntityFactory::create($ownerId);

        // When
        $result = $this->entityRepository->entityExists($ownerId, ParentFakeWritableEntity::getEntityName(), $entity->getId());

        // Then
        $this->assertTrue($result);
    }

    function testShouldEntityExistIsFalse()
    {
        // Given
        $ownerId = $this->faker->uuid;
        $entity = ParentFakeEntityFactory::create($ownerId);

        // When
        $result = $this->entityRepository->entityExists($ownerId, ParentFakeWritableEntity::getEntityName(), $this->faker->uuid);

        // Then
        $this->assertFalse($result);
    }

    function testGetEntityPathHierarchy()
    {
        $userOwnerId = $this->faker->uuid;

        $parentEntity = ParentFakeEntityFactory::create($userOwnerId);
        $childEntity = ChildFakeEntityFactory::create($parentEntity->getId(), $userOwnerId);

        $entityReference = new EntityReference(ChildFakeWritableEntity::getEntityName(), $childEntity->getId());

        // When
        $result = $this->entityRepository->getEntityPathHierarchy($entityReference);

        // Then
        $this->assertCount(2, $result);
        $this->assertEquals($parentEntity->getId(), $result[0]->getId());
        $this->assertEquals($childEntity->getId(), $result[1]->getId());
    }

    function testGetCountIsSuccess()
    {
        // Given
        $userOwnerId = $this->faker->uuid;
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userOwnerId));

        // When
        $result = $this->entityRepository->getCount($userOwnerId, ParentFakeWritableEntity::getEntityName());

        // Then
        $this->assertEquals(count($parentsEntities), $result);
    }

    function testSearchEntitiesWithFilterRestriction()
    {
        // Given
        $horus = Horus::getInstance();
        $horus->setEntityRestrictions(
            [
                new FilterEntityRestriction(ReadableFakeEntity::getEntityName(), [
                    new ParameterFilter(ReadableFakeEntity::ATTR_TYPE, "type1")
                ])
            ]
        );

        $entityRepository = new EloquentEntityRepository(
            $horus->getEntityMapper(),
            $this->cacheRepository,
            new DateTimeUtil(),
            $horus->getConfig()
        );

        $ownerId = $this->faker->uuid;
        $entities = $this->generateCountArray(fn() => ReadableFakeEntityFactory::create(), 20);
        $countExpected = count(array_filter($entities, fn(ReadableFakeEntity $entity) => $entity->type === "type1"));

        $this->cacheRepository->shouldReceive("exists")->andReturn(false);
        $this->cacheRepository->shouldReceive("set")->once();

        // When
        $entitiesResult = $entityRepository->searchEntities($ownerId, ReadableFakeEntity::getEntityName());

        // Then
        $this->assertCount($countExpected, $entitiesResult);
        $this->assertEquals(count($entities) - $countExpected, count($entities) - count($entitiesResult));
    }

    function testSearchRawEntitiesByReferenceIsSuccess()
    {
        // Given
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create());
        // Others records
        $this->generateArray(fn() => ParentFakeEntityFactory::create());

        foreach ($parentsEntities as $parentEntity) {
            $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId()));
        }

        $entityReferences = array_map(fn(ParentFakeWritableEntity $entity) => new EntityReference(ParentFakeWritableEntity::getEntityName(), $entity->getId()), $parentsEntities);

        // When
        $result = $this->entityRepository->searchRawEntitiesByReference(...$entityReferences);

        // Then
        $this->assertCount(count($parentsEntities), $result);

        //-------------- Validates the entities dont contains related entities
        foreach ($result as $entityData) {
            foreach ($entityData->getData() as $key => $value) {
                $this->assertFalse(str_starts_with($key, "_"));
            }
        }
    }

    function testInsertEntitiesWhenChildIsEarly()
    {
        // Given
        Schema::disableForeignKeyConstraints();

        $parentOrigin = ParentFakeEntityFactory::create();
        $childPrime = EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ChildFakeWritableEntity::getEntityName(), ChildFakeEntityFactory::newData($parentOrigin->getId()), now()->subDays(1)->toDateTimeImmutable());

        /**
         * @var EntityOperation[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ParentFakeWritableEntity::getEntityName(), ParentFakeEntityFactory::newData($this->faker->name), now()->subHours(5)->toDateTimeImmutable()
        ));
        /**
         * @var EntityOperation[] $childEntities
         */
        $childEntities = $this->generateArray(fn() => EntityOperationFactory::createEntityInsert(
            $this->faker->uuid,
            ChildFakeWritableEntity::getEntityName(), ChildFakeEntityFactory::newData($parentsEntities[0]->id), now()->toDateTimeImmutable()
        ));
        $operations = array_merge($parentsEntities, $childEntities, [$childPrime]);
        shuffle($operations);

        // When
        $this->entityRepository->insert(...$operations);

        // Then
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), count($parentsEntities) + 1);
        $this->assertDatabaseCount(ChildFakeWritableEntity::getTableName(), count($childEntities) + 1);
    }

    function testSearchEntitiesReadableMustBeCached()
    {
        // Given
        $ownerId = $this->faker->uuid;

        $entities = $this->generateArray(function () {
            return ReadableFakeEntityFactory::create();
        });
        $entitiesFromCache = array(new EntityData(ReadableFakeEntity::getEntityName()));

        $this->cacheRepository->shouldReceive("exists")->andReturn(false, true);
        $this->cacheRepository->shouldReceive("get")->andReturn($entitiesFromCache);
        $this->cacheRepository->shouldReceive("set")->once();

        // When
        $entitiesResult = $this->entityRepository->searchEntities($ownerId, ReadableFakeEntity::getEntityName());

        // Then
        $this->assertCount(count($entities), $entitiesResult);

        // Valida cache
        $this->assertEquals($entitiesFromCache, $this->entityRepository->searchEntities($ownerId, ReadableFakeEntity::getEntityName()));
    }

    function testInsertInBatchesOfTwentyFiveHundredIsSuccess()
    {
        // Given
        ChildFakeWritableEntity::query()->forceDelete();
        ParentFakeWritableEntity::query()->forceDelete();

        $ownerId = $this->faker->uuid;
        $batchSize = EloquentEntityRepository::BATCH_SIZE + 500; // More than 2500 to test batching
        $operations = [];

        // Generate operations for parent entities that will be processed in batches
        for ($i = 0; $i < $batchSize; $i++) {
            $operations[] = EntityOperationFactory::createEntityInsert(
                $ownerId,
                ParentFakeWritableEntity::getEntityName(),
                ParentFakeEntityFactory::newData(),
                now()->toDateTimeImmutable()
            );
        }

        // When
        $this->entityRepository->insert(...$operations);

        // Then
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), $batchSize);

        // Verify that all records were inserted correctly with proper hashes
        $insertedEntities = ParentFakeWritableEntity::all();
        $this->assertCount($batchSize, $insertedEntities);

        foreach ($insertedEntities as $entity) {
            $this->assertNotNull($entity->{WritableEntitySynchronizable::ATTR_SYNC_HASH});
            $this->assertEquals($ownerId, $entity->{WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID});
            $this->assertNotNull($entity->{WritableEntitySynchronizable::ATTR_SYNC_CREATED_AT});
            $this->assertNotNull($entity->{WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT});
        }
    }

    function testInsertMultipleEntitiesInBatchesIsSuccess()
    {
        // Given
        ChildFakeWritableEntity::query()->forceDelete();
        ParentFakeWritableEntity::query()->forceDelete();

        $ownerId = $this->faker->uuid;
        $parentBatchSize = EloquentEntityRepository::BATCH_SIZE + 100; // More than 2500 to test batching
        $childBatchSize = EloquentEntityRepository::BATCH_SIZE + 300; // More than 2500 to test batching
        $operations = [];

        // Create parent entities first to get valid parent IDs
        $parentEntitiesForRelation = [];
        for ($i = 0; $i < $childBatchSize; $i++) {
            $parentEntitiesForRelation[] = ParentFakeEntityFactory::create($ownerId);
        }

        // Generate operations for parent entities
        for ($i = 0; $i < $parentBatchSize; $i++) {
            $operations[] = EntityOperationFactory::createEntityInsert(
                $ownerId,
                ParentFakeWritableEntity::getEntityName(),
                ParentFakeEntityFactory::newData(),
                now()->toDateTimeImmutable()
            );
        }

        // Generate operations for child entities
        for ($i = 0; $i < $childBatchSize; $i++) {
            $operations[] = EntityOperationFactory::createEntityInsert(
                $ownerId,
                ChildFakeWritableEntity::getEntityName(),
                ChildFakeEntityFactory::newData($parentEntitiesForRelation[$i]->getId()),
                now()->toDateTimeImmutable()
            );
        }

        shuffle($operations);

        // When
        $this->entityRepository->insert(...$operations);

        // Then
        // Verify parent entities count (including the ones created for relation + the new ones)
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), $parentBatchSize + $childBatchSize);

        // Verify child entities count
        $this->assertDatabaseCount(ChildFakeWritableEntity::getTableName(), $childBatchSize);

        // Verify that records are properly inserted with batching
        $parentEntities = ParentFakeWritableEntity::where(WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID, $ownerId)->get();
        $childEntities = ChildFakeWritableEntity::where(WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID, $ownerId)->get();

        // Check that all entities have proper sync attributes
        foreach ($parentEntities as $entity) {
            $this->assertNotNull($entity->{WritableEntitySynchronizable::ATTR_SYNC_HASH});
            $this->assertEquals($ownerId, $entity->{WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID});
        }

        foreach ($childEntities as $entity) {
            $this->assertNotNull($entity->{WritableEntitySynchronizable::ATTR_SYNC_HASH});
            $this->assertEquals($ownerId, $entity->{WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID});
        }
    }
}
