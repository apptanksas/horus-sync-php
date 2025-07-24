<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Model\SyncJob;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use AppTank\Horus\Illuminate\Database\SyncJobModel;

/**
 * @internal Class EloquentSyncJobRepository
 *
 * This class implements the `SyncJobRepository` interface using Eloquent models to interact with the database.
 *
 * @package AppTank\Horus\Repository
 */
class EloquentSyncJobRepository implements SyncJobRepository
{

    /**
     * Saves a sync job to the repository.
     *
     * @param SyncJob $syncJob The sync job to be saved.
     * @return void
     * @throws \Throwable
     */
    function save(SyncJob $syncJob): void
    {
        $data = $this->parseData($syncJob);
        $query = SyncJobModel::query();
        $query = $query->where(SyncJobModel::ATTR_ID, $syncJob->id);

        if ($query->exists()) {
            $query->first()->updateOrFail($data);
            return;
        }

        $model = new SyncJobModel($data);
        $model->saveOrFail();
    }

    /**
     * Searches for a sync job by its ID.
     *
     * @param string $id The ID of the sync job to search for.
     * @return SyncJob|null The sync job with the specified ID, or null if no job is found.
     */
    function search(string $id): ?SyncJob
    {
        $item = SyncJobModel::query()
            ->where(SyncJobModel::ATTR_ID, $id)->first();

        if (is_null($item)) {
            return null;
        }

        return new SyncJob(
            $item->getId(),
            $item->getUserId(),
            $item->getStatus(),
            $item->getResultAt(),
            $item->getDownloadUrl()
        );
    }

    /**
     * Parses the sync job data into an array for saving to the database.
     *
     * @param SyncJob $syncJob The sync job to parse.
     * @return array The parsed data.
     */
    private function parseData(SyncJob $syncJob): array
    {
        return [
            SyncJobModel::ATTR_ID => $syncJob->id,
            SyncJobModel::ATTR_STATUS => $syncJob->status->value,
            SyncJobModel::ATTR_DOWNLOAD_URL => $syncJob->downloadUrl,
            SyncJobModel::ATTR_RESULTED_AT => $syncJob->resultAt,
            SyncJobModel::FK_USER_ID => $syncJob->userId
        ];
    }

} 