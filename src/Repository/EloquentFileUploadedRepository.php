<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Model\FileUploaded;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;

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
        $item = SyncFileUploadedModel::query()->where(SyncFileUploadedModel::ATTR_ID, $id)->first();

        if (is_null($item)) {
            return null;
        }

        return new FileUploaded(
            $item->getId(),
            $item->getMimeType(),
            $item->getPath(),
            $item->getPublicUrl(),
            $item->getOwnerId()
        );
    }

    function delete(string $id): void
    {
        $result = SyncFileUploadedModel::query()->where(SyncFileUploadedModel::ATTR_ID, $id)->delete();

        if ($result === 0) {
            throw new \Exception('Failed to delete file upload');
        }
    }

    private function parseData(FileUploaded $file): array
    {
        return [
            SyncFileUploadedModel::ATTR_ID => $file->id,
            SyncFileUploadedModel::ATTR_MIME_TYPE => $file->mimeType,
            SyncFileUploadedModel::ATTR_PATH => $file->path,
            SyncFileUploadedModel::ATTR_PUBLIC_URL => $file->publicUrl,
            SyncFileUploadedModel::FK_OWNER_ID => $file->ownerId,
        ];
    }
}