<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Model\SyncJob;

/**
 * @internal Interface SyncJobRepository
 *
 * Represents a repository for managing synchronization jobs. This interface defines methods for saving and searching
 * sync jobs.
 *
 * @package AppTank\Horus\Core\Repository
 *
 * @author John Ospina
 * Year: 2024
 */
interface SyncJobRepository
{
    /**
     * Saves a sync job to the repository.
     *
     * @param SyncJob $syncJob The sync job to be saved.
     * @return void
     */
    function save(SyncJob $syncJob): void;

    /**
     * Searches for a sync job by its ID.
     *
     * @param string $id The ID of the sync job to search for.
     * @return SyncJob|null The sync job with the specified ID, or null if no job is found.
     */
    function search(string $id): ?SyncJob;
} 