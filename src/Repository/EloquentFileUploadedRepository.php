<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Model\FileUploaded;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;
use Illuminate\Support\Facades\DB;

class EloquentFileUploadedRepository implements FileUploadedRepository
{

    public function __construct(
        private ?string $connectionName = null,
    )
    {
    }

    function save(FileUploaded $file): void
    {
        $table = (is_null($this->connectionName)) ? DB::table(SyncFileUploadedModel::TABLE_NAME) :
            DB::connection($this->connectionName)->table(SyncFileUploadedModel::TABLE_NAME);

        $data = $this->parseData($file);

        if (!$table->insert($data)) {
            throw new \Exception('Failed to save file upload');
        }
    }

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
        $table = (is_null($this->connectionName)) ? DB::table(SyncFileUploadedModel::TABLE_NAME) :
            DB::connection($this->connectionName)->table(SyncFileUploadedModel::TABLE_NAME);

        $result = $table->where(SyncFileUploadedModel::ATTR_ID, $id)->delete();

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