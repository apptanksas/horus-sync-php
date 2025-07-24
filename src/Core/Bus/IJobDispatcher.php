<?php

namespace AppTank\Horus\Core\Bus;

use AppTank\Horus\Core\JobType;

/**
 * @internal Interface IJobDispatcher
 *
 * This interface defines the contract for a job dispatcher that handles the dispatching of jobs
 * based on their type and associated data.
 *
 * @package AppTank\Horus\Core\Bus
 */
interface IJobDispatcher
{
    /**
     * Dispatches a job of the specified type with the provided data.
     *
     * @param JobType $type The type of job to be dispatched.
     * @param array $data The data associated with the job.
     *
     * @return void
     */
    function dispatch(JobType $type, array $data): void;
}