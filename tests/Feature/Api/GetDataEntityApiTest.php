<?php

namespace Api;

use AppTank\Horus\Core\Auth\AccessLevel;
use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\UserActingAs;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ReadableFakeEntity;
use Tests\_Stubs\ReadableFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
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

    function setUp(): void
    {
        parent::setUp();
        Schema::disableForeignKeyConstraints();
    }

    function testGetEntitiesIsSuccess()
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $parentsEntities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId));

        foreach ($parentsEntities as $parentEntity) {
            $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userId));
        }

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_DATA->value, ParentFakeWritableEntity::getEntityName()));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities));
        $response->assertJsonStructure(self::JSON_SCHEME_PARENT);
    }

    function testGetEntitiesChildIsSuccess()
    {

        $userId = $this->faker->uuid;
        $parent = ParentFakeEntityFactory::create($userId);
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $entities = $this->generateArray(fn() => ChildFakeEntityFactory::create($parent->getId(), $userId));


        // When
        $response = $this->get(route(RouteName::GET_ENTITY_DATA->value, ChildFakeWritableEntity::getEntityName()));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($entities));
        $response->assertJsonStructure(self::JSON_SCHEME_CHILD);
    }

    function testGetEntitiesChildIsSuccessByIds()
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
        $response = $this->get(route(RouteName::GET_ENTITY_DATA->value, [ParentFakeWritableEntity::getEntityName(), "after" => $updatedAtTarget]));

        // Then
        $response->assertOk();
        $response->assertJsonCount($countExpected);
        $response->assertJsonStructure(self::JSON_SCHEME_PARENT);
    }

    function testGetEntitiesLookupIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($ownerId));
        $entities = $this->generateArray(fn() => ReadableFakeEntityFactory::create());

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_DATA->value, ReadableFakeEntity::getEntityName()));

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
            $grants[] = new EntityGranted($userOwnerId, new EntityReference(ParentFakeWritableEntity::getEntityName(), $entity->getId()), AccessLevel::all());
            return $entity;
        });

        foreach ($parentsEntities as $parentEntity) {
            $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userOwnerId));
        }
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userInvitedId, $grants, new UserActingAs($userOwnerId)));

        // When
        $url = route(RouteName::GET_ENTITY_DATA->value, [ParentFakeWritableEntity::getEntityName(),
            "ids" => implode(",", array_map(fn(ParentFakeWritableEntity $entity) => $entity->getId(), $parentsEntities))]);
        $response = $this->get($url);

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($parentsEntities));
        $response->assertJsonStructure(self::JSON_SCHEME_PARENT);
    }

    function testGetEntitiesGrantedSuccessWithNoUserActingAs()
    {
        $userOwnerId = $this->faker->uuid;
        $userInvitedId = $this->faker->uuid;
        $grants = [];

        $parentsEntities = $this->generateArray(function () use ($userOwnerId, &$grants) {
            $entity = ParentFakeEntityFactory::create($userOwnerId);
            $grants[] = new EntityGranted($userOwnerId, new EntityReference(ParentFakeWritableEntity::getEntityName(), $entity->getId()), AccessLevel::all());
            return $entity;
        });

        foreach ($parentsEntities as $parentEntity) {
            $this->generateArray(fn() => ChildFakeEntityFactory::create($parentEntity->getId(), $userOwnerId));
        }
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userInvitedId, $grants));

        // When
        $url = route(RouteName::GET_ENTITY_DATA->value, [ParentFakeWritableEntity::getEntityName(),
            "ids" => implode(",", array_map(fn(ParentFakeWritableEntity $entity) => $entity->getId(), $parentsEntities))]);
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
        $grants = [new EntityGranted($userOwnerId, new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentEntity->getId()), AccessLevel::all())];
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userInvitedId, $grants, new UserActingAs($userOwnerId)));

        // When
        $url = route(RouteName::GET_ENTITY_DATA->value, [ChildFakeWritableEntity::getEntityName(),
            "ids" => implode(",", array_map(fn(ChildFakeWritableEntity $entity) => $entity->getId(), $childEntities))]);
        $response = $this->get($url);

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($childEntities));
        $response->assertJsonStructure(self::JSON_SCHEME_CHILD);
    }

    function testGetEntitiesGrantedUsingUserActingAsSuccessValidatingAccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userInvitedId = $this->faker->uuid;

        $parentEntity = ParentFakeEntityFactory::create($userOwnerId);

        $childEntities = $this->generateArray(function () use ($userOwnerId, &$grants, $parentEntity) {
            return ChildFakeEntityFactory::create($parentEntity->getId(), $userOwnerId);
        });

        // Add grants to the parent entity
        $grants = [new EntityGranted($userOwnerId, new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentEntity->getId()), AccessLevel::all())];

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userInvitedId, $grants, new UserActingAs($userOwnerId)))
            ->setConfig(new Config(true));

        // When
        $url = route(RouteName::GET_ENTITY_DATA->value, [ChildFakeWritableEntity::getEntityName(),
            "ids" => implode(",", array_map(fn(ChildFakeWritableEntity $entity) => $entity->getId(), $childEntities))]);
        $response = $this->get($url);

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($childEntities));
        $response->assertJsonStructure(self::JSON_SCHEME_CHILD);
    }
}