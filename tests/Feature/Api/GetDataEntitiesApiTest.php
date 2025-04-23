<?php

namespace Api;

use AppTank\Horus\Core\Auth\AccessLevel;
use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\AdjacentFakeEntityFactory;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ReadableFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\Feature\Api\ApiTestCase;

class GetDataEntitiesApiTest extends ApiTestCase
{
    use RefreshDatabase;

    function testGetDataEntitiesSuccess()
    {
        $userId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

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
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId, [
            ParentFakeWritableEntity::ATTR_VALUE_NULLABLE => $this->faker->word
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
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId, [
            ParentFakeWritableEntity::ATTR_VALUE_NULLABLE => $this->faker->word
        ]));

        foreach ($parentsEntities as $parentEntity) {
            $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userId));
            AdjacentFakeEntityFactory::create($parentEntity->getId(), $userId);
        }

        $lookups = $this->generateArray(fn() => ReadableFakeEntityFactory::create());

        // When
        $response = $this->get(route(RouteName::GET_DATA_ENTITIES->value));


        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities) + count($lookups));
    }


    function testGetDataEntitiesSuccessIsEmpty()
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

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
        Horus::getInstance()->setUserAuthenticated(new UserAuth($ownerId));

        /**
         * @var ParentFakeWritableEntity[] $parentsEntities
         */
        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId, [
            WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT => $this->getDateTimeUtil()->getFormatDate($updatedAt)
        ]));

        // Generate entities before the updatedAt
        $this->generateArray(fn() => ParentFakeEntityFactory::create($ownerId, [
            WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT => $this->getDateTimeUtil()->getFormatDate($this->faker->dateTimeBetween(endDate: $updatedAt)->getTimestamp())
        ]));

        $updatedAtTarget = $updatedAt - 1;
        $countExpected = count(array_filter($parentsEntities, fn(ParentFakeWritableEntity $entity) => $entity->getUpdatedAt()->getTimestamp() > $updatedAtTarget));

        // When
        $response = $this->get(route(RouteName::GET_DATA_ENTITIES->value, ['after' => $updatedAtTarget]));

        // Then
        $response->assertJsonCount($countExpected);
    }


    function testGetDataEntitiesWithGrantsSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userInvitedId = $this->faker->uuid;
        $grants = [];

        $parentsEntitiesOwner = $this->generateArray(function () use ($userOwnerId, &$grants) {
            $entity = ParentFakeEntityFactory::create($userOwnerId);
            $grants[] = new EntityGranted($userOwnerId,
                new EntityReference(ParentFakeWritableEntity::getEntityName(), $entity->getId()),
                AccessLevel::all());
            return $entity;
        });

        $parentsEntitiesInvited = $this->generateArray(fn() => ParentFakeEntityFactory::create($userInvitedId));

        $childEntities = [];

        foreach ($parentsEntitiesOwner as $parentEntity) {
            $childEntities[$parentEntity->getId()] = $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userOwnerId));
            AdjacentFakeEntityFactory::create($parentEntity->getId(), $userOwnerId);
        }

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userInvitedId, $grants))->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_DATA_ENTITIES->value));


        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntitiesOwner) + count($parentsEntitiesInvited));
    }

}