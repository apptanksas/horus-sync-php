<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\File\SyncFileStatus;
use AppTank\Horus\Core\Model\FileUploaded;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;

/**
 * @internal Class EloquentFileUploadedRepository
 *
 * This class implements the `FileUploadedRepository` interface using Eloquent models to interact with the database.
 *
 * @package AppTank\Horus\Repository
 */
class EloquentFileUploadedRepository implements FileUploadedRepository
{

    /**
     * Saves a file upload to the repository.
     *
     * @param FileUploaded $file The file upload to be saved.
     * @return void
     * @throws \Throwable
     */
    function save(FileUploaded $file): void
    {
        $data = $this->parseData($file);
        $query = SyncFileUploadedModel::query();
        $query = $query->where(SyncFileUploadedModel::ATTR_ID, $file->id);

        if ($query->exists()) {
            $query->first()->updateOrFail($data);
            return;
        }

        $model = new SyncFileUploadedModel($data);
        $model->saveOrFail();
    }

    /**
     * Searches for a file upload by its ID.
     *
     * @param string $id The ID of the file upload to search for.
     * @return FileUploaded|null The file upload with the specified ID, or null if no file is found.
     */
    function search(string $id): ?FileUploaded
    {
        $item = SyncFileUploadedModel::query()
            ->where(SyncFileUploadedModel::ATTR_ID, $id)->first();

        if (is_null($item)) {
            return null;
        }

        return new FileUploaded(
            $item->getId(),
            $item->getMimeType(),
            $item->getPath(),
            $item->getPublicUrl(),
            $item->getOwnerId(),
            SyncFileStatus::from($item->getStatus())
        );
    }

    /**
     * Search files in batch
     *
     * @param string[] $ids
     * @return FileUploaded[]
     */
    function searchInBatch(string $userId, array $ids): array
    {
        $output = [];
        $result = SyncFileUploadedModel::query()->whereIn(SyncFileUploadedModel::ATTR_ID, $ids)->get();

        foreach ($result as $item) {
            $output[] = new FileUploaded(
                $item->getId(),
                $item->getMimeType(),
                $item->getPath(),
                $item->getPublicUrl(),
                $item->getOwnerId(),
                SyncFileStatus::from($item->getStatus())
            );
        }

        return $output;
    }

    /**
     * Deletes a file upload by its ID.
     *
     * @param string $id The ID of the file upload to delete.
     * @return void
     */
    function delete(string $id): void
    {
        $result = SyncFileUploadedModel::query()->where(SyncFileUploadedModel::ATTR_ID, $id)->delete();

        if ($result === 0) {
            throw new \Exception('Failed to delete file upload');
        }
    }

    /**
     * Parses the file upload data into an array for saving to the database.
     *
     * @param FileUploaded $file The file upload to parse.
     * @return array The parsed data.
     */
    private function parseData(FileUploaded $file): array
    {
        return [
            SyncFileUploadedModel::ATTR_ID => $file->id,
            SyncFileUploadedModel::ATTR_MIME_TYPE => $file->mimeType,
            SyncFileUploadedModel::ATTR_PATH => $file->path,
            SyncFileUploadedModel::ATTR_PUBLIC_URL => $file->publicUrl,
            SyncFileUploadedModel::ATTR_STATUS => $file->status->value,
            SyncFileUploadedModel::FK_OWNER_ID => $file->ownerId
        ];
    }


}