<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;

/**
 * @internal Class EloquentEntityAccessValidatorRepository
 *
 * Implementation of the entity access validation repository.
 * Provides mechanisms to validate if a user has access to a specific entity
 * based on ownership, granted permissions, and hierarchical relationships between entities.
 *
 * @author John Ospina
 * @year 2024
 */
readonly class EloquentEntityAccessValidatorRepository implements EntityAccessValidatorRepository
{
    /**
     * Constructor of the `EloquentEntityAccessValidatorRepository` class.
     *
     * @param EntityMapper $entityMapper Entity mapper used to obtain entity classes and hierarchies.
     * @param Config $config Application configuration.
     * @param EntityRepository $entityRepository Repository for entity operations.
     */
    public function __construct(
        private EntityMapper     $entityMapper,
        private Config           $config,
        private EntityRepository $entityRepository
    )
    {
    }

    /**
     * Validates if a user has access to a specific entity with a given permission.
     *
     * @param UserAuth $userAuth User authentication requesting access.
     * @param EntityReference $entityReference Reference to the entity to access.
     * @param Permission $permission Permission to validate.
     * @return bool Returns `true` if the user has access; otherwise, `false`.
     */
    public function canAccessEntity(UserAuth        $userAuth,
                                    EntityReference $entityReference,
                                    Permission      $permission): bool
    {
        // 1. Validate if access validation is disabled
        if (!$this->config->validateAccess) {
            return true;
        }

        // 2. Validate if user is owner
        if ($this->isEntityOwner($entityReference->entityName, $entityReference->entityId, $userAuth->userId)) {
            return true;
        }

        // 3. Validate if user has granted permission
        if ($userAuth->hasGranted($entityReference->entityName, $entityReference->entityId, $permission)) {
            return true;
        }

        // 4. Validate if user has access on cascade by entity granted
        if ($this->canAccessOnCascade($userAuth->entityGrants, $entityReference, $permission)) {
            return true;
        }

        return false;
    }

    /**
     * Validates if a user had previous access to a specific entity with a given permission.
     *
     * @param UserAuth $userAuth User authentication.
     * @param EntityReference $entityReference Reference to the entity to check.
     * @return bool Returns `true` if the user had previous access; otherwise, `false`.
     */
    public function thereWasAccessEntityPreviously(UserAuth $userAuth, EntityReference $entityReference, Permission $permission): bool
    {
        $callbackValidateEntityWasGranted = $this->config->getCallbackValidateEntityWasGranted() ?? [];

        if (empty($callbackValidateEntityWasGranted)) {
            return false;
        }

        /**
         * @var EntityGranted[] $entitiesGranted
         */
        $entitiesGranted = $callbackValidateEntityWasGranted($userAuth, $entityReference);

        foreach ($entitiesGranted as $entityGrant) {
            if ($entityGrant->entityReference->entityName === $entityReference->entityName &&
                $entityGrant->entityReference->entityId === $entityReference->entityId &&
                $entityGrant->accessLevel->can($permission)) {
                return true;
            }
        }

        return ($this->canAccessOnCascade($entitiesGranted, $entityReference, $permission));
    }

    /**
     * Checks if the user has access to the entity through a hierarchy of entities.
     *
     * @param EntityGranted[] $userAuth User authentication.
     * @param EntityReference $entityReference Reference to the entity to access.
     * @param Permission $permissionRequested Permission to validate.
     * @return bool Returns `true` if the user has access through hierarchy; otherwise, `false`.
     */
    private function canAccessOnCascade(array $entitiesGranted, EntityReference $entityReference, Permission $permissionRequested): bool
    {
        if (empty($entitiesGranted)) {
            return false;
        }

        if (!$this->canAccessByParentHierarchyWithPath($entitiesGranted, $entityReference)) {
            return false;
        }

        $entityHierarchy = $this->entityRepository->getEntityPathHierarchy($entityReference);

        foreach ($entitiesGranted as $entityGrant) {
            if ($this->entityGrantedIsInEntityHierarchy($entityGrant, $entityHierarchy) &&
                $entityGrant->accessLevel->can($permissionRequested)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the user has access to the entity through the hierarchy of parent entities.
     *
     * @param EntityGranted[] $entitiesGranted User authentication.
     * @param EntityReference $entityReference Reference to the entity to access.
     * @return bool Returns `true` if the user has access through parent hierarchy; otherwise, `false`.
     */
    private function canAccessByParentHierarchyWithPath(array $entitiesGranted, EntityReference $entityReference): bool
    {
        $paths = $this->entityMapper->getPaths();

        foreach ($paths as $path) {
            foreach ($entitiesGranted as $entityGrant) {
                $parentIndexPath = array_search($entityGrant->entityReference->entityName, $path);
                $childIndexPath = array_search($entityReference->entityName, $path);

                // Validate if entity granted is parent of entity reference to access
                if (in_array($entityGrant->entityReference->entityName, $path) &&
                    in_array($entityReference->entityName, $path) &&
                    $parentIndexPath < $childIndexPath) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if the granted entity is present in the entity hierarchy.
     *
     * @param EntityGranted $entityGranted Granted entity.
     * @param array $entityHierarchy Built entity hierarchy.
     * @return bool Returns `true` if the granted entity is in the hierarchy; otherwise, `false`.
     */
    private function entityGrantedIsInEntityHierarchy(EntityGranted $entityGranted, array $entityHierarchy): bool
    {
        foreach ($entityHierarchy as $entity) {
            if ($entity->entityName == $entityGranted->entityReference->entityName &&
                $entity->getId() == $entityGranted->entityReference->entityId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if a user is the owner of a specific entity.
     *
     * @param string $entityName Name of the entity.
     * @param string $entityId ID of the entity.
     * @param string|int $userId ID of the user.
     * @return bool Returns `true` if the user is the owner of the entity; otherwise, `false`.
     */
    private function isEntityOwner(string $entityName, string $entityId, string|int $userId): bool
    {
        /**
         * @var WritableEntitySynchronizable $entityClass
         */
        $entityClass = $this->getEntityClass($entityName);

        return $entityClass::isOwner($entityId, $userId);
    }

    /**
     * Gets the class of the entity corresponding to an entity name.
     *
     * @param string $entityName Name of the entity.
     * @return string Name of the entity class.
     */
    private function getEntityClass(string $entityName): string
    {
        return $this->entityMapper->getEntityClass($entityName);
    }
}
