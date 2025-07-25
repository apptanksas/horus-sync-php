<?php

namespace AppTank\Horus\Illuminate\Job;

use AppTank\Horus\Application\Get\GetDataEntities;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Model\SyncJob;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
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

    private readonly GetDataEntities $useCase;


    function __construct(
        private readonly EntityRepository                $entityRepository,
        private readonly EntityAccessValidatorRepository $accessValidatorRepository,
        private readonly SyncJobRepository               $syncJobRepository,
        private readonly IFileHandler                    $fileHandler,
        private readonly Config                          $config
    )
    {
        $this->useCase = new GetDataEntities(
            $entityRepository,
            $accessValidatorRepository
        );
    }

    public function handle(UserAuth $userAuth, SyncJob $job): void
    {
        // Update the job status to IN_PROGRESS
        $jobInProgress = new SyncJob($job->id, $job->userId, SyncJobStatus::IN_PROGRESS);
        $this->syncJobRepository->save($jobInProgress);

        // Get the data entities using the use case
        $data = json_encode($this->useCase->__invoke($userAuth, $job->checkpoint));
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
    }
}
