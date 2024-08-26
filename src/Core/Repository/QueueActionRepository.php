<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Model\QueueAction;

/**
 * @internal Interface QueueActionRepository
 *
 * Defines the contract for managing queue actions within a repository. Implementations of this
 * interface should handle the saving, retrieving, and querying of queue actions.
 *
 * @package AppTank\Horus\Core\Repository
 *
 * @author John Ospina
 * Year: 2024
 */
interface QueueActionRepository
{
    /**
     * Saves multiple queue actions to the repository.
     *
     * @param QueueAction ...$actions The queue actions to be saved.
     * @return void
     */
    function save(QueueAction ...$actions): void;

    /**
     * Retrieves the last queue action for a specific user owner ID.
     *
     * @param string|int $userOwnerId The ID of the user owner whose last action is to be retrieved.
     * @return QueueAction|null The last queue action for the specified user owner ID, or null if no actions are found.
     */
    function getLastAction(string|int $userOwnerId): ?QueueAction;

    /**
     * Retrieves a list of queue actions for a specific user owner ID, with optional filters.
     *
     * @param string|int $userOwnerId The ID of the user owner whose actions are to be retrieved.
     * @param int|null $afterTimestamp Optional timestamp to filter actions that occurred after this time.
     * @param int[] $excludeDateTimes Optional array of timestamps to exclude from the results.
     * @return QueueAction[] An array of queue actions that match the specified criteria.
     */
    function getActions(string|int $userOwnerId, ?int $afterTimestamp = null, array $excludeDateTimes = []): array;
}
