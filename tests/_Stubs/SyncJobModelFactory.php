<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\SyncJobStatus;
use AppTank\Horus\Illuminate\Database\SyncJobModel;

class SyncJobModelFactory
{
    public static function create(
        ?string $userId = null,
        ?SyncJobStatus $status = null,
        ?string $downloadUrl = null,
        ?\DateTimeInterface $resultedAt = null,
        ?\DateTimeInterface $createdAt = null,
        ?int $checkpoint = null
    ): SyncJobModel
    {
        $faker = \Faker\Factory::create();

        $data = [
            SyncJobModel::ATTR_ID => $faker->uuid,
            SyncJobModel::FK_USER_ID => $userId ?? $faker->uuid,
            SyncJobModel::ATTR_STATUS => $status ? $status->value : SyncJobStatus::PENDING->value,
            SyncJobModel::ATTR_DOWNLOAD_URL => $downloadUrl ?? $faker->optional()->url,
            SyncJobModel::ATTR_RESULTED_AT => $resultedAt ? $resultedAt->format('Y-m-d H:i:s') : null,
            SyncJobModel::ATTR_CHECKPOINT => $checkpoint
        ];

        if ($createdAt) {
            $data[SyncJobModel::CREATED_AT] = $createdAt->format('Y-m-d H:i:s');
        }

        $model = new SyncJobModel($data);

        $model->saveOrFail();

        return $model;
    }
} 