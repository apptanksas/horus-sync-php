<?php

namespace Tests\Unit\Repository;


use AppTank\Horus\Core\Auth\AccessLevel;
use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Horus;
use AppTank\Horus\Repository\EloquentEntityAccessValidatorRepository;
use Tests\_Stubs\ChildFakeEntity;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ParentFakeEntity;
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

        $this->repository = new EloquentEntityAccessValidatorRepository($mapper, $config);
    }

    function test_when_user_owner_then_can_access_own_entity()
    {
        $userOwnerId = $this->faker->uuid;

        $entity = ParentFakeEntityFactory::create($userOwnerId);
        $entityName = ParentFakeEntity::getEntityName();
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
        $entityName = ParentFakeEntity::getEntityName();
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
        $entityName = ParentFakeEntity::getEntityName();
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
        $entityName = ParentFakeEntity::getEntityName();
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
                new EntityReference(ParentFakeEntity::getEntityName(), $entity->getId()),
                AccessLevel::new(Permission::READ))
        ]);

        // When
        $entityName = ChildFakeEntity::getEntityName();
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
                new EntityReference(ParentFakeEntity::getEntityName(), $entity->getId()),
                AccessLevel::new(Permission::READ, Permission::CREATE))
        ]);

        // When
        $entityName = ChildFakeEntity::getEntityName();
        $entityId = $childEntity->getId();
        $canAccess = $this->repository->canAccessEntity($userAuth, new EntityReference($entityName, $entityId), Permission::DELETE);

        // Then
        $this->assertFalse($canAccess);
    }

}
