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
     * Update or delete action
     *
     * Represents the action of either updating or deleting an existing entity or record. (Only for client use)
     */
    case UPDELETE = "UD";
}
