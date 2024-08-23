<?php

namespace Api;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\AdjacentFakeEntityFactory;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\LookupFakeEntityFactory;
use Tests\_Stubs\ParentFakeEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\Feature\Api\ApiTestCase;

class GetDataEntitiesApiTest extends ApiTestCase
{
    use RefreshDatabase;

    function testGetDataEntitiesSuccess()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId));
        $childEntities = [];

        foreach ($parentsEntities as $parentEntity) {
            $childEntities[$parentEntity->getId()] = $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userId));
            AdjacentFakeEntityFactory::create($parentEntity->getId(), $userId);
        }

        // When
        $response = $this->get(route(RouteName::GET_DATA_ENTITIES->value));


        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities));
        $response->assertJsonStructure([
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
                    'value_enum',
                    '_children' => [
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
                        ],
                    ],
                    '_adjacent' => [
                        'entity',
                        'data' => [
                            'id',
                            'sync_owner_id',
                            'sync_hash',
                            'sync_created_at',
                            'sync_updated_at',
                            'name',
                            'parent_id',
                        ],
                    ],
                ],
            ],
        ]);
    }

    function testGetDataEntitiesWithNullablesSuccess()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId, [
            ParentFakeEntity::ATTR_VALUE_NULLABLE => $this->faker->word
        ]));

        $childEntities = [];

        foreach ($parentsEntities as $parentEntity) {
            $childEntities[$parentEntity->getId()] = $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userId));
            AdjacentFakeEntityFactory::create($parentEntity->getId(), $userId);
        }

        // When
        $response = $this->get(route(RouteName::GET_DATA_ENTITIES->value));


        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities));
        $response->assertJsonStructure([
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
                    'value_enum',
                    'value_nullable',
                    '_children' => [
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
                        ],
                    ],
                    '_adjacent' => [
                        'entity',
                        'data' => [
                            'id',
                            'sync_owner_id',
                            'sync_hash',
                            'sync_created_at',
                            'sync_updated_at',
                            'name',
                            'parent_id',
                        ],
                    ],
                ],
            ],
        ]);
    }

    function testGetDataEntitiesWithLookupsSuccess()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId, [
            ParentFakeEntity::ATTR_VALUE_NULLABLE => $this->faker->word
        ]));

        foreach ($parentsEntities as $parentEntity) {
            $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userId));
            AdjacentFakeEntityFactory::create($parentEntity->getId(), $userId);
        }

        $lookups = $this->generateArray(fn() => LookupFakeEntityFactory::create());

        // When
        $response = $this->get(route(RouteName::GET_DATA_ENTITIES->value));


        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities) + count($lookups));
    }


    function testGetDataEntitiesSuccessIsEmpty()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setUserAuthenticated(new UserAuth($userId));

        // When
        $response = $this->get(route(RouteName::GET_DATA_ENTITIES->value));

        // Then
        $response->assertOk();
        $response->assertJsonCount(0);
    }

    function testGetDataEntitiesAfterTimestamp()
    {
        $ownerId = $this->faker->uuid;
        $updatedAt = $this->faker->dateTimeBetween()->getTimestamp();
        HorusContainer::getInstance()->setUserAuthenticated(new UserAuth($ownerId));

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
        $response = $this->get(route(RouteName::GET_DATA_ENTITIES->value, ['after' => $updatedAtTarget]));

        // Then
        $response->assertJsonCount($countExpected);
    }
}