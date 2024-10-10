<?php

namespace Tests\_Stubs;

use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;

class SyncFileUploadedModelFactory
{
    public static function create(?string $userId = null, string $fileUrl = null): SyncFileUploadedModel
    {
        $faker = \Faker\Factory::create();

        $model = new SyncFileUploadedModel(
            [
                SyncFileUploadedModel::ATTR_ID => $faker->uuid,
                SyncFileUploadedModel::ATTR_MIME_TYPE => $faker->mimeType(),
                SyncFileUploadedModel::ATTR_PATH => $faker->filePath(),
                SyncFileUploadedModel::ATTR_PUBLIC_URL => $fileUrl ?? $faker->imageUrl,
                SyncFileUploadedModel::FK_OWNER_ID => $userId ?? $faker->uuid,
            ]
        );

        $model->saveOrFail();

        return $model;
    }
}