<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\File\SyncFileStatus;
use AppTank\Horus\Core\Model\FileUploaded;

class FileUploadedFactory
{
    public static function create(?string $id = null, ?string $userId = null, ?SyncFileStatus $status = null): FileUploaded
    {
        $faker = \Faker\Factory::create();

        return new FileUploaded(
            $id ?? $faker->uuid,
            $faker->mimeType(),
            $faker->filePath(),
            $faker->imageUrl,
            $userId ?? $faker->uuid,
            $status ? $status->value : SyncFileStatus::PENDING
        );
    }
}