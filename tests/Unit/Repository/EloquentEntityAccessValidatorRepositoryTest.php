<?php

namespace Tests\Unit\Repository;


use AppTank\Horus\Core\Auth\AccessLevel;
use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserActingAs;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use AppTank\Horus\Repository\DefaultCacheRepository;
use AppTank\Horus\Repository\EloquentEntityAccessValidatorRepository;
use AppTank\Horus\Repository\EloquentEntityRepository;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\TestCase;

class EloquentEntityAccessValidatorRepositoryTest extends TestCase
{

    private EloquentEntityAccessValidatorRepository $repository;

    public function setUp(): void
    {
        parent::setUp();
        $mapper = Horus::getInstance()->getEntityMapper();
        $config = new Config(true);
        $entityRepository = new EloquentEntityRepository($mapper, new DefaultCacheRepository(), new DateTimeUtil(), $config);

        $this->repository = new EloquentEntityAccessValidatorRepository($mapper, $config, $entityRepository);
    }

    function test_when_user_owner_then_can_access_own_entity()
    {
        $userOwnerId = $this->faker->uuid;

        $entity = ParentFakeEntityFactory::create($userOwnerId);
        $entityName = ParentFakeWritableEntity::getEntityName();
        $entityId = $entity->getId();

        $userAuth = new UserAuth($userOwnerId);

        // When
        foreach (Permission::cases() as $permission) {
            $canAccess = $this->repository->canAccessEntity($userAuth, new EntityReference($entityName, $entityId), $permission);
            // Then
            $this->assertTrue($canAccess);
        }
    }

    function test_when_any_user_then_can_not_access_entity()
    {
        $entity = ParentFakeEntityFactory::create();
        $entityName = ParentFakeWritableEntity::getEntityName();
        $entityId = $entity->getId();

        $userAuth = new UserAuth($this->faker->uuid);

        // When
        foreach (Permission::cases() as $permission) {
            $canAccess = $this->repository->canAccessEntity($userAuth, new EntityReference($entityName, $entityId), $permission);
            // Then
            $this->assertFalse($canAccess);
        }
    }

    function test_when_user_granted_can_access_an_entity_from_owner_with_read_permission()
    {
        $userInvitedId = $this->faker->uuid;
        $userOwnerId = $this->faker->uuid;

        $entity = ParentFakeEntityFactory::create($userOwnerId);
        $entityName = ParentFakeWritableEntity::getEntityName();
        $entityId = $entity->getId();

        $userAuth = new UserAuth($userInvitedId, [
            new EntityGranted($userOwnerId,
                new EntityReference($entityName, $entityId),
                AccessLevel::new(Permission::READ))
        ]);

        // When
        $canAccess = $this->repository->canAccessEntity($userAuth, new EntityReference($entityName, $entityId), Permission::READ);

        // Then
        $this->assertTrue($canAccess);
    }

    function test_when_user_granted_can_not_access_an_entity_from_owner_with_create_permission()
    {
        $userInvitedId = $this->faker->uuid;
        $userOwnerId = $this->faker->uuid;

        $entity = ParentFakeEntityFactory::create($userOwnerId);
        $entityName = ParentFakeWritableEntity::getEntityName();
        $entityId = $entity->getId();

        $userAuth = new UserAuth($userInvitedId, [
            new EntityGranted($userOwnerId,
                new EntityReference($entityName, $entityId),
                AccessLevel::new(Permission::READ))
        ]);

        // When
        $canAccess = $this->repository->canAccessEntity($userAuth, new EntityReference($entityName, $entityId), Permission::CREATE);

        // Then
        $this->assertFalse($canAccess);
    }

    function test_when_user_granted_can_access_an_entity_child_from_entity_parent()
    {
        $userInvitedId = $this->faker->uuid;
        $userOwnerId = $this->faker->uuid;

        $entity = ParentFakeEntityFactory::create($userOwnerId);
        $childEntity = ChildFakeEntityFactory::create($entity->getId(), $userOwnerId);

        $userAuth = new UserAuth($userInvitedId, [
            new EntityGranted($userOwnerId,
                new EntityReference(ParentFakeWritableEntity::getEntityName(), $entity->getId()),
                AccessLevel::new(Permission::READ))
        ]);

        // When
        $entityName = ChildFakeWritableEntity::getEntityName();
        $entityId = $childEntity->getId();
        $canAccess = $this->repository->canAccessEntity($userAuth, new EntityReference($entityName, $entityId), Permission::READ);

        // Then
        $this->assertTrue($canAccess);
    }

    function test_when_user_granted_can_not_access_an_entity_to_delete_child_from_entity_parent()
    {
        $userInvitedId = $this->faker->uuid;
        $userOwnerId = $this->faker->uuid;

        $entity = ParentFakeEntityFactory::create($userOwnerId);
        $childEntity = ChildFakeEntityFactory::create($entity->getId(), $userOwnerId);

        $userAuth = new UserAuth($userInvitedId, [
            new EntityGranted($userOwnerId,
                new EntityReference(ParentFakeWritableEntity::getEntityName(), $entity->getId()),
                AccessLevel::new(Permission::READ, Permission::CREATE))
        ]);

        // When
        $entityName = ChildFakeWritableEntity::getEntityName();
        $entityId = $childEntity->getId();
        $canAccess = $this->repository->canAccessEntity($userAuth, new EntityReference($entityName, $entityId), Permission::DELETE);

        // Then
        $this->assertFalse($canAccess);
    }

    function test_when_user_update_as_user_guest()
    {
        $userOwnerId = "809a7eec-af67-4b61-87e0-ce9da74f1dcd";
        $parentId = "e0392744-1b09-49c2-a14f-9883cf3b52ab";
        $entityName = ParentFakeWritableEntity::getEntityName();
        $userAuth = new UserAuth("d104e048-e781-4986-8af0-0c56394e9c02", [
            new EntityGranted($userOwnerId, new EntityReference($entityName, $parentId), AccessLevel::decode("CRUD"))
        ], new UserActingAs($userOwnerId));

        ParentFakeEntityFactory::create($userOwnerId, ["id" => $parentId]);
        $childEntity = ChildFakeEntityFactory::create($parentId, $userOwnerId);

        // When
        $canAccess = $this->repository->canAccessEntity($userAuth, new EntityReference(ChildFakeWritableEntity::getEntityName(), $childEntity->getId()), Permission::UPDATE);

        // Then
        $this->assertTrue($canAccess);
    }
}
