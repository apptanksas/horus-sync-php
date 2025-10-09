<?php

namespace AppTank\Horus\Core;

use AppTank\Horus\Core\Trait\EnumIterator;

/**
 * @internal Enum SyncAction
 *
 * Represents the types of synchronization actions that can be performed.
 * This enum is used to specify the action to be taken during data synchronization processes.
 *
 * @package AppTank\Horus\Core
 */
enum SyncAction: string
{
    use EnumIterator;

    /**
     * Insert action
     *
     * Represents the action of inserting a new entity or record.
     */
    case INSERT = "I";

    /**
     * Update action
     *
     * Represents the action of updating an existing entity or record.
     */
    case UPDATE = "U";

    /**
     * Delete action
     *
     * Represents the action of deleting an existing entity or record.
     */
    case DELETE = "D";

    /**
     * Move action
     *
     * Represents the action of moving an entity or record from one location to another.
     */
    case MOVE = "M";
}
