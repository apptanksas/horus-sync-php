<?php

namespace Api;

use AppTank\Horus\Core\Auth\AccessLevel;
use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\UserActingAs;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;
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
        HorusContainer::getInstance()->setUserAuthenticated(new UserAuth($userId));

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
        HorusContainer::getInstance()->setUserAuthenticated(new UserAuth($userId));

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
        $response = $this->get(
            route(RouteName::GET_ENTITY_DATA->value, [ParentFakeEntity::getEntityName(), "after" => $updatedAtTarget]));

        // Then
        $response->assertOk();
        $response->assertJsonCount($countExpected);
        $response->assertJsonStructure(self::JSON_SCHEME_PARENT);
    }

    function testGetEntitiesLookupIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        HorusContainer::getInstance()->setUserAuthenticated(new UserAuth($ownerId));
        $entities = $this->generateArray(fn() => LookupFakeEntityFactory::create());

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_DATA->value, LookupFakeEntity::getEntityName()));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($entities));
        $response->assertJsonStructure(self::JSON_SCHEME_LOOKUP);
    }

    function testGetEntitiesGrantedSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userInvitedId = $this->faker->uuid;
        $grants = [];

        $parentsEntities = $this->generateArray(function () use ($userOwnerId, &$grants) {
            $entity = ParentFakeEntityFactory::create($userOwnerId);
            $grants[] = new EntityGranted($userOwnerId, new EntityReference(ParentFakeEntity::getEntityName(), $entity->getId()), AccessLevel::all());
            return $entity;
        });

        foreach ($parentsEntities as $parentEntity) {
            $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userOwnerId));
        }
        HorusContainer::getInstance()->setUserAuthenticated(new UserAuth($userInvitedId, $grants, new UserActingAs($userOwnerId)));

        // When
        $url = route(RouteName::GET_ENTITY_DATA->value, [ParentFakeEntity::getEntityName(),
            "ids" => implode(",", array_map(fn(ParentFakeEntity $entity) => $entity->getId(), $parentsEntities))]);
        $response = $this->get($url);

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities));
        $response->assertJsonStructure(self::JSON_SCHEME_PARENT);
    }

    function testGetEntitiesGrantedUsingUserActingAsSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userInvitedId = $this->faker->uuid;

        $parentEntity = ParentFakeEntityFactory::create($userOwnerId);

        $childEntities = $this->generateArray(function () use ($userOwnerId, &$grants, $parentEntity) {
            return ChildFakeEntityFactory::create($parentEntity->getId(), $userOwnerId);
        });

        // Add grants to the parent entity
        $grants = [new EntityGranted($userOwnerId, new EntityReference(ParentFakeEntity::getEntityName(), $parentEntity->getId()), AccessLevel::all())];
        HorusContainer::getInstance()->setUserAuthenticated(new UserAuth($userInvitedId, $grants, new UserActingAs($userOwnerId)));

        // When
        $url = route(RouteName::GET_ENTITY_DATA->value, [ChildFakeEntity::getEntityName(),
            "ids" => implode(",", array_map(fn(ChildFakeEntity $entity) => $entity->getId(), $childEntities))]);
        $response = $this->get($url);

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($childEntities));
        $response->assertJsonStructure(self::JSON_SCHEME_CHILD);
    }

}