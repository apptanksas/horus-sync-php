<?php

namespace AppTank\Horus\Application\Sync;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Bus\IJobDispatcher;
use AppTank\Horus\Core\JobType;
use AppTank\Horus\Core\Model\SyncJob;
use AppTank\Horus\Core\Repository\SyncJobRepository;

/**
 * @internal Class StartGenerateSyncDataJob
 *
 * This class is responsible for starting a job to generate synchronization data.
 * @package AppTank\Horus\Application\Sync
 */
readonly class StartGenerateSyncDataJob
{
    public function __construct(
        private SyncJobRepository $syncJobRepository,
        private IJobDispatcher    $jobDispatcher
    )
    {

    }

    /**
     * Invokes the job to generate synchronization data.
     *
     * @param UserAuth $userAuth The user authentication context.
     * @param string $syncId The ID of the sync job to be started.
     * @return void
     */
    function __invoke(UserAuth $userAuth, string $syncId, int|null $checkpoint = null): void
    {
        $syncJob = new SyncJob($syncId, $userAuth->userId, checkpoint: $checkpoint);

        $this->jobDispatcher->dispatch(JobType::GENERATE_SYNC_DATA, $userAuth, $syncJob);
        $this->syncJobRepository->save($syncJob);
    }
}