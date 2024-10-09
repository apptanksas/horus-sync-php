<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Model\FileUploaded;

class FileUploadedFactory
{
    public static function create(?string $userId = null): FileUploaded
    {
        $faker = \Faker\Factory::create();

        return new FileUploaded(
            $faker->uuid,
            $faker->mimeType(),
            $faker->filePath(),
            $faker->url,
            $userId ?? $faker->uuid,
        );
    }
}