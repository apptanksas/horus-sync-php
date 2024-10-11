<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\File\SyncFileStatus;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;

class SyncFileUploadedModelFactory
{
    public static function create(?string             $userId = null,
                                  string              $fileUrl = null,
                                  ?SyncFileStatus     $status = null,
                                  ?\DateTimeInterface $createdAt = null
    ): SyncFileUploadedModel
    {
        $faker = \Faker\Factory::create();

        $data = [
            SyncFileUploadedModel::ATTR_ID => $faker->uuid,
            SyncFileUploadedModel::ATTR_MIME_TYPE => $faker->mimeType(),
            SyncFileUploadedModel::ATTR_PATH => $faker->filePath(),
            SyncFileUploadedModel::ATTR_PUBLIC_URL => $fileUrl ?? $faker->imageUrl,
            SyncFileUploadedModel::FK_OWNER_ID => $userId ?? $faker->uuid,
            SyncFileUploadedModel::ATTR_STATUS => $status ? $status->value : SyncFileStatus::PENDING
        ];

        if ($createdAt) {
            $data[SyncFileUploadedModel::CREATED_AT] = $createdAt->format('Y-m-d H:i:s');
        }

        $model = new SyncFileUploadedModel($data);

        $model->saveOrFail();

        return $model;
    }
}