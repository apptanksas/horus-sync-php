<?php

namespace AppTank\Horus\Core;

use AppTank\Horus\Core\Trait\EnumIterator;

/**
 * @internal Enum SyncJobStatus
 *
 * This enum represents the possible statuses of a synchronization job.
 */
enum SyncJobStatus: int
{
    use EnumIterator;

    /**
     * The job is pending and has not yet started.
     */
    case PENDING = 0;

    /**
     * The job is currently in progress.
     */
    case IN_PROGRESS = 1;

    /**
     * The job has completed successfully.
     */
    case COMPLETED = 2;

    /**
     * The job has failed.
     */
    case FAILED = 3;
}