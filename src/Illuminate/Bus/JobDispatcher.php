<?php

namespace AppTank\Horus\Illuminate\Bus;


use AppTank\Horus\Core\Bus\IJobDispatcher;
use AppTank\Horus\Core\JobType;
use AppTank\Horus\Illuminate\Job\GenerateDataSyncJob;

/**
 * @internal Class JobDispatcher
 *
 * This class is responsible for dispatching jobs based on the provided JobType and data.
 *
 * @package AppTank\Horus\Illuminate\Bus
 */
class JobDispatcher implements IJobDispatcher
{

    /**
     * Dispatch a job based on the provided JobType and data.
     *
     * @param JobType $type
     * @param array $data
     * @return void
     */
    public function dispatch(JobType $type, ...$data): void
    {
        match ($type) {
            JobType::GENERATE_SYNC_DATA => GenerateDataSyncJob::dispatch(...$data)
        };
    }
}