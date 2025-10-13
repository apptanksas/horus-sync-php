<?php

namespace AppTank\Horus\Illuminate\Job;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Mapper\EntityMapper;
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
        Config                  $config,
        EntityMapper            $entityMapper
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
            $contentSorted = $this->sortContentByEntityLevel($content, $entityMapper);
            $fileUrl = $fileHandler->createDownloadableTemporaryFile($pathFile, $contentSorted, "application/x-ndjson");

            // Update the job with the download URL and result timestamp
            $jobCompleted = new SyncJob(
                $this->syncJob->id,
                $this->syncJob->userId,
                SyncJobStatus::SUCCESS,
                resultAt: now("UTC")->toImmutable(),
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

        foreach ($data as $attr => $entity) {

            if (!isset($entity["data"])) {
                throw new \InvalidArgumentException("Entity [$entity] at index {$attr} does not contain 'data' key.");
            }

            $data = array_reverse($entity["data"]);
            $entityFiltered = $this->filterRelations($entity);
            $content .= json_encode($entityFiltered) . PHP_EOL;

            foreach ($data as $key => $value) {

                // Check if the key represents a related entity with relation many
                if (str_starts_with($key, "_") and is_array($value) and !empty($value) && isset($value[0]["data"])) {
                    $content .= $this->createContentNdJson($value) . PHP_EOL;
                }

                // Check if the key represents a related entity with relation one
                if (str_starts_with($key, "_") and is_array($value) and !empty($value) && isset($value["entity"])) {
                    $content .= json_encode($this->filterRelations($value)) . PHP_EOL;
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

    private function sortContentByEntityLevel(string $contentNdJson, EntityMapper $entityMapper): string
    {
        $lines = explode(PHP_EOL, $contentNdJson);
        usort($lines, function ($a, $b) use ($entityMapper) {
            $entityALevel = $entityMapper->getHierarchicalLevel(json_decode($a, true)["entity"]);
            $entityBLevel = $entityMapper->getHierarchicalLevel(json_decode($b, true)["entity"]);

            return $entityALevel <=> $entityBLevel;
        });
        return implode(PHP_EOL, $lines);
    }
}
