<?php

namespace AppTank\Horus\Illuminate\Job;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Model\SyncJob;
use AppTank\Horus\Core\Repository\IGetDataEntitiesUseCase;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use AppTank\Horus\Core\SyncJobStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * @internal Class GenerateDataSyncJob
 *
 * Handles the generation of data synchronization jobs.
 * This job retrieves data entities and saves them to a file for download.
 *
 * @package AppTank\Horus\Illuminate\Job
 *
 * @author John Ospina
 * Year: 2024
 */
class GenerateDataSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    function __construct(
        private readonly IGetDataEntitiesUseCase $getDataEntitiesUseCase,
        private readonly SyncJobRepository       $syncJobRepository,
        private readonly IFileHandler            $fileHandler,
        private readonly Config                  $config
    )
    {
    }

    public function handle(UserAuth $userAuth, SyncJob $job): void
    {
        // Update the job status to IN_PROGRESS
        $jobInProgress = $job->cloneWithStatus(SyncJobStatus::IN_PROGRESS);
        $this->syncJobRepository->save($jobInProgress);

        try {
            // Get the data entities using the repository
            $data = json_encode($this->getDataEntitiesUseCase->__invoke($userAuth, $job->checkpoint));
            $pathFile = $this->config->getPathFilesSync() . "/{$job->id}.json";
            $fileUrl = $this->fileHandler->createDownloadableTemporaryFile($pathFile, $data, "application/json");

            // Update the job with the download URL and result timestamp
            $jobCompleted = new SyncJob(
                $job->id,
                $job->userId,
                SyncJobStatus::COMPLETED,
                resultAt: now()->toImmutable(),
                downloadUrl: $fileUrl,
                checkpoint: $job->checkpoint
            );
            $this->syncJobRepository->save($jobCompleted);
        } catch (\Throwable $e) {
            report($e);
            $this->syncJobRepository->save($job->cloneWithStatus(SyncJobStatus::FAILED));
        }

    }
}
