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
        private readonly UserAuth $userAuth,
        private readonly SyncJob  $syncJob
    )
    {
    }

    /**
     * Handle the job execution.
     *
     * This method retrieves data entities, creates an NDJSON file, and updates the sync job status.
     *
     * @param IGetDataEntitiesUseCase $getDataEntitiesUseCase Use case for retrieving data entities.
     * @param SyncJobRepository $syncJobRepository Repository for managing sync jobs.
     * @param IFileHandler $fileHandler File handler for creating downloadable files.
     * @param Config $config Configuration settings.
     */
    public function handle(
        IGetDataEntitiesUseCase $getDataEntitiesUseCase,
        SyncJobRepository       $syncJobRepository,
        IFileHandler            $fileHandler,
        Config                  $config
    ): void
    {
        // Update the job status to IN_PROGRESS
        $jobInProgress = $this->syncJob->cloneWithStatus(SyncJobStatus::IN_PROGRESS);
        $syncJobRepository->save($jobInProgress);

        try {
            // Get the data entities using the repository
            $data = $getDataEntitiesUseCase->__invoke($this->userAuth, $this->syncJob->checkpoint);
            $pathFile = $config->getPathFilesSync() . "/{$this->syncJob->id}.ndjson";

            $content = $this->createContentNdJson($data);
            $fileUrl = $fileHandler->createDownloadableTemporaryFile($pathFile, $content, "application/x-ndjson");

            // Update the job with the download URL and result timestamp
            $jobCompleted = new SyncJob(
                $this->syncJob->id,
                $this->syncJob->userId,
                SyncJobStatus::SUCCESS,
                resultAt: now()->toImmutable(),
                downloadUrl: $fileUrl,
                checkpoint: $this->syncJob->checkpoint
            );
            $syncJobRepository->save($jobCompleted);
        } catch (\Throwable $e) {
            report($e);
            $syncJobRepository->save($this->syncJob->cloneWithStatus(SyncJobStatus::FAILED));
        }

    }


    /**
     * Creates the content for the NDJSON file from the provided data.
     *
     * @param array $data The data to be converted to NDJSON format.
     * @return string The NDJSON formatted content.
     */
    private function createContentNdJson(array $data): string
    {
        $content = '';

        foreach ($data as $index => $entity) {

            $data = $entity["data"];
            $entityFiltered = $this->filterRelations($entity);
            $content .= json_encode($entityFiltered) . PHP_EOL;

            foreach ($data as $key => $value) {
                // Check if the key represents a related entity
                if (str_starts_with($key, "_") and is_array($value)) {
                    $content .= $this->createContentNdJson($value) . PHP_EOL;
                }
            }
        }

        return rtrim($content);
    }


    private function filterRelations(array $data): array
    {
        $output = [];
        foreach ($data as $key => $value) {

            if (str_starts_with($key, "_")) {
                continue;
            }

            if (is_array($value)) {
                $output[$key] = $this->filterRelations($value);
                continue;
            }

            $output[$key] = $value;

        }

        return $output;
    }
}
