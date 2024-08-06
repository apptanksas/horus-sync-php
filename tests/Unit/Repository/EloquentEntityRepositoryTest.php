<?php

namespace Tests\Unit\Repository;


use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Core\Model\EntityOperation;
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


}
