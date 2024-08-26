<?php

namespace AppTank\Horus\Core\Auth;

use AppTank\Horus\Core\Trait\EnumIterator;

/**
 * Enum Permission
 *
 * This enum represents different types of permissions that can be assigned to users or entities.
 * It provides a set of constants representing various actions that can be performed on an entity.
 *
 * @author John Ospina
 * Year: 2024
 */
enum Permission: string
{
    use EnumIterator;

    case READ = "R"; // Read permission
    case CREATE = "C"; // Create permission
    case UPDATE = "U"; // Update permission
    case DELETE = "D"; // Delete permission
}