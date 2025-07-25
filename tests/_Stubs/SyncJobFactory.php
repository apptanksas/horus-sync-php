<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Model\SyncJob;
use AppTank\Horus\Core\SyncJobStatus;

class SyncJobFactory
{
    public static function create(
        ?string             $id = null,
        ?string             $userId = null,
        ?SyncJobStatus      $status = null,
        ?\DateTimeImmutable $resultAt = null,
        ?string             $downloadUrl = null,
        ?int                $checkpoint = null
    ): SyncJob
    {
        $faker = \Faker\Factory::create();

        return new SyncJob(
            $id ?? $faker->uuid,
            $userId ?? $faker->uuid,
            $status ?? SyncJobStatus::PENDING,
            $resultAt,
            $downloadUrl ?? $faker->optional()->url,
            $checkpoint ?? $faker->dateTime()->getTimestamp(),
        );
    }
} 