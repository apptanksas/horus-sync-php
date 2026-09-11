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
    function getLastActionByUserOwnerId(string|int $userOwnerId): ?QueueAction;

    /**
     * Retrieves the last queue action for a specific array of user owner IDs.
     *
     * @param array $ownerIds The IDs of the user owners whose last action is to be retrieved.
     * @return QueueAction|null The last queue action for the specified user owner ID, or null if no actions are found.
     */
    function getLastActionByOwners(array $ownerIds): ?QueueAction;

    /**
     * Retrieves actions combining restricted owners (filtered by date or event ID) and unrestricted owners (always included).
     *
     * @param array|int|string $filteredOwnerIds Owners subject to the date/event exclusion logic.
     * @param int|null $afterTimestamp Global time filter (applies to everything).
     * @param array $excludeDateTimes Dates to exclude for the filtered owners.
     * @param array $alwaysIncludeOwnerIds Owners whose actions are always retrieved (ignoring exclusions).
     * @param string|null $afterEventId Filter actions after the specified event ID.
     * @param array $excludeEventIds Event IDs to exclude for the filtered owners.
     * @param int|null $limit Maximum number of actions to retrieve.
     */
    public function getActions(
        array|int|string $filteredOwnerIds,
        ?int             $afterTimestamp = null,
        array            $excludeDateTimes = [],
        array            $alwaysIncludeOwnerIds = [],
        ?string          $afterEventId = null,
        array            $excludeEventIds = [],
        ?int             $limit = null
    ): array;

    /**
     * Checks if actions are registered for the given event IDs.
     *
     * @param array $eventIds List of event IDs to check.
     * @return array Associative array mapping each event ID to a boolean indicating if it is registered.
     */
    public function checkExistsByEventIds(array $eventIds): array;
}
