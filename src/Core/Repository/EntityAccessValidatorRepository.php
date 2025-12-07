<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;

/**
 * @internal Interface EntityAccessValidatorRepository
 *
 * Defines the contract for validating access to entities based on user permissions.
 * Implementations of this interface should provide the logic for checking if a user
 * has the necessary permissions to access a given entity.
 *
 * @package AppTank\Horus\Core\Repository
 *
 * @author John Ospina
 * Year: 2024
 */
interface EntityAccessValidatorRepository
{
    /**
     * Checks if the user has the required permission to access the entity.
     *
     * @param UserAuth $userAuth The user whose permissions are being checked.
     * @param EntityReference $entityReference The entity for which access is being validated.
     * @param Permission $permission The permission being checked.
     *
     * @return bool True if the user has access to the entity with the given permission; otherwise, false.
     */
    public function canAccessEntity(UserAuth $userAuth, EntityReference $entityReference, Permission $permission): bool;

    /**
     * Checks if the user there was access to the entity previously.
     *
     * @param UserAuth $userAuth The user whose access history is being checked.
     * @param EntityReference $entityReference The entity for which previous access is being validated.
     *
     * @return bool True if the user had access to the entity previously; otherwise, false.
     */
    public function thereWasAccessEntityPreviously(UserAuth $userAuth, EntityReference $entityReference, Permission $permission): bool;
}
