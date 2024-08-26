<?php

namespace Api;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ChildFakeEntity;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\LookupFakeEntity;
use Tests\_Stubs\LookupFakeEntityFactory;
use Tests\_Stubs\ParentFakeEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\TestCase;

class GetDataEntityApiTest extends TestCase
{
    use RefreshDatabase;

    private const  array JSON_SCHEME_CHILD = [
        '*' => [
            'entity',
            'data' => [
                'id',
                'sync_owner_id',
                'sync_hash',
                'sync_created_at',
                'sync_updated_at',
                'primary_int_value',
                'primary_string_value',
                'int_value',
                'float_value',
                'string_value',
                'boolean_value',
                'timestamp_value',
                'parent_id',
            ],
        ]
    ];


    private const  array JSON_SCHEME_PARENT = [
        '*' => [
            'entity',
            'data' => [
                'id',
                'sync_owner_id',
                'sync_hash',
                'sync_created_at',
                'sync_updated_at',
                'name',
                'color',
                '_children' => self::JSON_SCHEME_CHILD
            ],
        ],
    ];

    private const array JSON_SCHEME_LOOKUP = [
        '*' => [
            'entity',
            'data' => [
                'id',
                'name',
            ],
        ],
    ];

    function testGetEntitiesIsSuccess()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId));

        foreach ($parentsEntities as $parentEntity) {
            $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userId));
        }

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_DATA->value, ParentFakeEntity::getEntityName()));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities));
        $response->assertJsonStructure(self::JSON_SCHEME_PARENT);
    }

    function testGetEntitiesChildIsSuccess()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        $entities = $this->generateArray(fn() => ChildFakeEntityFactory::create(null, $userId));

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_DATA->value, ChildFakeEntity::getEntityName()));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($entities));
        $response->assertJsonStructure(self::JSON_SCHEME_CHILD);
    }

    function testGetEntitiesChildIsSuccessByIds()
    {
        $ownerId = $this->faker->uuid;
        $updatedAt = $this->faker->dateTimeBetween()->getTimestamp();
        HorusContainer::getInstance()->setAuthenticatedUserId($ownerId);

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
        $response = $this->get(
            route(RouteName::GET_ENTITY_DATA->value, [ParentFakeEntity::getEntityName(),"after" => $updatedAtTarget]));

        // Then
        $response->assertOk();
        $response->assertJsonCount($countExpected);
        $response->assertJsonStructure(self::JSON_SCHEME_PARENT);
    }

    function testGetEntitiesLookupIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($ownerId);
        $entities=$this->generateArray(fn() => LookupFakeEntityFactory::create());

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_DATA->value, LookupFakeEntity::getEntityName()));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($entities));
        $response->assertJsonStructure(self::JSON_SCHEME_LOOKUP);
    }

}