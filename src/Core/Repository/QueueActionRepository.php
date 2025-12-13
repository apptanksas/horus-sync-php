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
     * Retrieves actions combining restricted owners (filtered by date) and unrestricted owners (always included).
     *
     * @param array|int|string $filteredOwnerIds Owners subject to the date exclusion logic.
     * @param int|null $afterTimestamp Global time filter (applies to everything).
     * @param array $excludeDateTimes Dates to exclude for the filtered owners.
     * @param array $alwaysIncludeOwnerIds Owners whose actions are always retrieved (ignoring exclusions).
     */
    public function getActions(
        array|int|string $filteredOwnerIds,
        ?int             $afterTimestamp = null,
        array            $excludeDateTimes = [],
        array            $alwaysIncludeOwnerIds = [] // Nuevo parámetro
    ): array;
}
