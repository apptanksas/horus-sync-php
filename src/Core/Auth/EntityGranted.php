<?php

namespace AppTank\Horus\Core\Auth;

use AppTank\Horus\Core\Entity\EntityReference;

/**
 * Class EntityGranted
 *
 * Represents the permissions granted to a user for a specific entity.
 * This includes the user owner ID, the reference to the entity, and the access level granted.
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class EntityGranted
{
    /**
     * @param string|int $userOwnerId ID of the user who owns the entity.
     * @param EntityReference $entityReference Reference to the entity.
     * @param AccessLevel $accessLevel Access level granted to the user for the entity.
     */
    function __construct(
        public string|int      $userOwnerId,
        public EntityReference $entityReference,
        public AccessLevel     $accessLevel
    )
    {

    }
}