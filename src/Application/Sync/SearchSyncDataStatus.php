<?php

namespace AppTank\Horus\Application\Sync;

use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Core\Repository\SyncJobRepository;

/**
 * @internal Class SearchSyncDataStatus
 *
 * Handles the search for synchronization job status by ID.
 *
 * @package AppTank\Horus\Application\Sync
 *
 * @author John Ospina
 * Year: 2025
 */
readonly class SearchSyncDataStatus
{
    public function __construct(
        private SyncJobRepository $syncJobRepository,
    )
    {

    }

    /**
     * Invokes the search for a synchronization job by its ID.
     *
     * @param string $syncId The ID of the synchronization job to search for.
     * @return array The details of the synchronization job as an associative array.
     * @throws ClientException If the synchronization job with the given ID is not found.
     */
    function __invoke(string $syncId): array
    {
        $result = $this->syncJobRepository->search($syncId);

        if (is_null($result)) {
            throw new ClientException("Sync job with ID $syncId not found");
        }

        return $result->toArray();
    }
}
