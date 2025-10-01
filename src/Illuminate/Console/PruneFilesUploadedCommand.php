<?php

namespace AppTank\Horus\Illuminate\Console;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\File\IFileReferenceValidator;
use AppTank\Horus\Core\File\SyncFileStatus;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\SyncJobStatus;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;
use AppTank\Horus\Illuminate\Database\SyncJobModel;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * @internal Class PruneFilesUploadedCommand
 *
 * Command responsible for deleting files uploaded that are no longer needed.
 *
 * @package AppTank\Horus\Illuminate\Console
 *
 * @author John Ospina
 * Year: 2024
 */
class PruneFilesUploadedCommand extends Command
{
    /**
     * The name of the command.
     */
    const string COMMAND_NAME = 'horus:prune';

    /**
     * The output message format for logging the number of deleted pending files.
     */
    const string OUTPUT_FORMAT_MESSAGE_PENDING_FILES = 'Deleted %d pending files.';
    const string OUTPUT_FORMAT_MESSAGE_DELETED_FILES = 'Deleted %d files referenced in deleted data of entity %s.';
    const string OUTPUT_FORMAT_MESSAGE_PENDING_FILE_VALIDATIONS = 'Validated %d file references in pending files.';

    /**
     * The signature of the command, which includes an optional expirationDays argument.
     * Default value for expirationDays is 7 days.
     */
    protected $signature = self::COMMAND_NAME . " {expirationDays=7}";

    /**
     * The description of the command that will appear in the Artisan CLI.
     */
    protected $description = "Prune files uploaded that are no longer needed.";

    /**
     * Handler responsible for file operations.
     *
     * @var IFileHandler
     */
    private IFileHandler $fileHandler;

    /**
     * Mapper responsible for retrieving and managing entity data.
     *
     * @var EntityMapper
     */
    private EntityMapper $entityMapper;

    private Config $config;

    /**
     * Constructor initializes the command and assigns file handler and entity mapper instances.
     */
    public function __construct(
        private readonly IFileReferenceValidator $fileReferenceValidator
    )
    {
        parent::__construct();
        $this->fileHandler = Horus::getInstance()->getFileHandler();
        $this->entityMapper = Horus::getInstance()->getEntityMapper();
        $this->config = Horus::getInstance()->getConfig();
    }

    /**
     * Execute the console command.
     * Retrieves the expiration days argument and invokes methods to delete files.
     * Catches and logs any exceptions encountered during execution.
     *
     * @return int Command exit code indicating success or failure.
     */
    public function handle()
    {
        try {
            $expirationDays = intval($this->argument('expirationDays'));
            $this->validateFilesPendingFileReferences();
            $this->deleteFilesReferencedInDataDeleted($expirationDays);
            $this->deleteFilesPendingExpired($expirationDays);
            $this->deleteFilesSyncExpired();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return CommandAlias::FAILURE;
        }
        return CommandAlias::SUCCESS;
    }

    /**
     * Validates files that are in "PENDING" status and are referenced by entities in the system.
     * It checks each entity for parameters that reference files and ensures that the referenced files exist and are valid.
     * If a file reference is invalid, it will be handled by the FileReferenceValidator.
     */
    private function validateFilesPendingFileReferences(): void
    {
        $counter = 0;
        $fileReferenceParameters = $this->getFileReferenceParameters();

        foreach ($fileReferenceParameters as $entityName => $parameters) {

            /**
             * @var EntitySynchronizable $entityClass The entity class that can be synchronized.
             */
            $entityClass = $this->entityMapper->getEntityClass($entityName);
            $arraySelect = array_merge(array_map(fn($item) => $entityClass::getTableName() . ".$item", $parameters), [
                $entityClass::getTableName() . "." . WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID,
                $entityClass::getTableName() . "." . WritableEntitySynchronizable::ATTR_ID
            ]);

            $entityClass::query()->join(SyncFileUploadedModel::TABLE_NAME, function (JoinClause $join) use ($entityClass, $parameters) {

                foreach ($parameters as $index => $parameter) {
                    $attributeReference = $entityClass::getTableName() . "." . $parameter;
                    $fileReferenceId = SyncFileUploadedModel::TABLE_NAME . "." . SyncFileUploadedModel::ATTR_ID;
                    if ($index === 0) {
                        $join->on(function ($query) use ($attributeReference, $fileReferenceId) {
                            $query->on($attributeReference, '=', $fileReferenceId)->where(SyncFileUploadedModel::ATTR_STATUS, SyncFileStatus::PENDING);
                        });
                    } else {
                        $join->orOn(function ($query) use ($attributeReference, $fileReferenceId) {
                            $query->on($attributeReference, '=', $fileReferenceId)->where(SyncFileUploadedModel::ATTR_STATUS, SyncFileStatus::PENDING);
                        });
                    }
                }

            })->select($arraySelect)->distinct()->chunk(5000, function (Collection $records) use ($parameters, $entityName, &$counter) {

                foreach ($records->toArray() as $record) {

                    $userAuth = new UserAuth($record[WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID]);
                    $entityId = $record[WritableEntitySynchronizable::ATTR_ID];

                    // Validate each file reference parameter
                    foreach ($parameters as $parameter) {
                        $fileReference = $record[$parameter];
                        $this->fileReferenceValidator->validate($userAuth, $fileReference, new EntityReference($entityName, $entityId));
                        $counter++;
                    }
                }
            });
        }

        $this->info(sprintf(self::OUTPUT_FORMAT_MESSAGE_PENDING_FILE_VALIDATIONS, $counter));
    }

    /**
     * Deletes files that are marked as "PENDING" and were created before the expiration period.
     * Uses the `expirationDays` to determine which files are considered expired.
     *
     * @param int $expirationDays The number of days before which files are considered expired.
     */
    private function deleteFilesPendingExpired(int $expirationDays): void
    {
        $counter = 0;

        // Fetch files that are pending and created before the expiration date
        $filesUploaded = SyncFileUploadedModel::query()
            ->where(SyncFileUploadedModel::ATTR_STATUS, SyncFileStatus::PENDING->value())
            ->where(SyncFileUploadedModel::CREATED_AT, '<', now()->subDays($expirationDays)->toDateTimeString())
            ->get([SyncFileUploadedModel::ATTR_ID, SyncFileUploadedModel::ATTR_PATH]);

        // Delete each file and remove it from the database
        foreach ($filesUploaded as $fileUploaded) {
            if ($this->fileHandler->delete($fileUploaded->getPath())) {
                $fileUploaded->update([SyncFileUploadedModel::ATTR_STATUS => SyncFileStatus::DELETED->value()]);
                $counter++;
            }
        }

        // Log the number of deleted files
        $this->info(sprintf(self::OUTPUT_FORMAT_MESSAGE_PENDING_FILES, $counter));
    }

    /**
     * Deletes files that are referenced by entities marked as deleted in the system.
     * It checks records from entities that have been soft-deleted and removes any files they reference.
     *
     * @param int $expirationDays The number of days to determine if the entity data is considered old.
     */
    private function deleteFilesReferencedInDataDeleted(int $expirationDays): void
    {
        $fileReferenceParameters = $this->getFileReferenceParameters();

        /**
         * @var EntitySynchronizable $entityClass The entity class that can be synchronized.
         */
        foreach ($fileReferenceParameters as $entityName => $parameters) {

            $entityClass = $this->entityMapper->getEntityClass($entityName);

            $arraySelect = array_merge(array_map(fn($item) => $entityClass::getTableName() . ".$item", $parameters), [
                $entityClass::getTableName() . "." . WritableEntitySynchronizable::ATTR_ID
            ]);

            // Fetch soft-deleted records for the entity that are older than expiration days
            $recordsDeleted = $entityClass::onlyTrashed()->join(SyncFileUploadedModel::TABLE_NAME, function (JoinClause $join) use ($entityClass, $parameters, $expirationDays) {

                foreach ($parameters as $index => $parameter) {

                    $columnEntityId = $entityClass::getTableName() . "." . $parameter;
                    $columnEntityDeletedAt = $entityClass::getTableName() . "." . EntitySynchronizable::ATTR_SYNC_DELETED_AT;
                    $fileReferenceId = SyncFileUploadedModel::TABLE_NAME . "." . SyncFileUploadedModel::ATTR_ID;
                    $columnFileStatus = SyncFileUploadedModel::TABLE_NAME . "." . SyncFileUploadedModel::ATTR_STATUS;

                    if ($index === 0) {
                        $join->on(function ($query) use ($columnEntityId, $fileReferenceId, $columnFileStatus, $columnEntityDeletedAt, $expirationDays) {
                            $query->on($columnEntityId, '=', $fileReferenceId)
                                ->where($columnEntityDeletedAt, '!=', null)
                                ->where($columnEntityDeletedAt, '<', now()->subDays($expirationDays)->toDateTimeString())
                                ->where($columnFileStatus, SyncFileStatus::PENDING);

                        });
                    } else {
                        $join->orOn(function ($query) use ($columnEntityId, $fileReferenceId, $columnFileStatus, $columnEntityDeletedAt, $expirationDays) {
                            $query->on($columnEntityId, '=', $fileReferenceId)
                                ->where($columnEntityDeletedAt, '!=', null)
                                ->where($columnEntityDeletedAt, '<', now()->subDays($expirationDays)->toDateTimeString())
                                ->where($columnFileStatus, SyncFileStatus::PENDING);
                        });
                    }
                }

            })->select($arraySelect)->get();

            // For each deleted record, remove associated files
            foreach ($recordsDeleted as $recordDeleted) {
                foreach ($parameters as $parameterReferenceFile) {
                    $fileId = $recordDeleted->{$parameterReferenceFile};
                    $fileUploaded = SyncFileUploadedModel::query()->find($fileId);

                    // If file exists, delete it and mark as deleted in the database
                    if (!is_null($fileUploaded) && $this->fileHandler->delete($fileUploaded->getPath())) {
                        $fileUploaded->update([SyncFileUploadedModel::ATTR_STATUS => SyncFileStatus::DELETED->value()]);
                    }
                }
            }

            // Log the number of deleted files
            $this->info(sprintf(self::OUTPUT_FORMAT_MESSAGE_DELETED_FILES, $recordsDeleted->count(), $entityClass::getEntityName()));
        }
    }

    /**
     * Deletes files that are older than one hour and have a status of SUCCESS.
     * These files are considered expired and are removed from the system.
     * The status of the sync jobs is updated to COMPLETED after deletion.
     */
    private function deleteFilesSyncExpired(): void
    {
        try {
            $syncFileIdDeleted = [];
            $syncFilesExpired = SyncJobModel::query()->where(SyncJobModel::ATTR_RESULTED_AT, '<', now()->hour(1)->toDateTimeString())
                ->where(SyncJobModel::ATTR_STATUS, SyncJobStatus::SUCCESS->value())
                ->where(SyncJobModel::ATTR_RESULTED_AT, '!=', null)
                ->where(SyncJobModel::ATTR_DOWNLOAD_URL, '!=', null)
                ->get([SyncJobModel::ATTR_ID, SyncJobModel::ATTR_DOWNLOAD_URL])->toArray();

            $this->info(sprintf('Found %d sync files that are expired.', count($syncFilesExpired)));

            foreach ($syncFilesExpired as $file) {
                $filename = basename(parse_url($file[SyncJobModel::ATTR_DOWNLOAD_URL], PHP_URL_PATH));
                $pathFile = $this->config->getPathFilesSync() . "/$filename";
                if ($this->fileHandler->delete($pathFile)) {
                    $syncFileIdDeleted[] = $file[SyncJobModel::ATTR_ID];
                }
            }

            // Update the status of the sync jobs to indicate that the files have been deleted
            SyncJobModel::query()->whereIn(SyncJobModel::ATTR_ID, $syncFileIdDeleted)->update([SyncJobModel::ATTR_STATUS => SyncJobStatus::COMPLETED->value()]);

        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Retrieves and merges file reference parameters from the configuration and entity definitions.
     * This method ensures that all relevant parameters for file references are collected for each entity type.
     *
     * @return array An associative array where keys are entity names and values are arrays of file reference parameters.
     */
    private function getFileReferenceParameters(): array
    {
        $fileReferenceParameters = Horus::getInstance()->getConfig()->extraParametersReferenceFile;
        $entities = $this->entityMapper->getEntities();

        // Iterate over each entity type
        foreach ($entities as $entityClass) {
            $entityParameters = $this->entityMapper->getParametersReferenceFile($entityClass::getEntityName());
            $extraEntityParameters = $fileReferenceParameters[$entityClass::getEntityName()] ?? [];
            $parameters = (is_array($extraEntityParameters) ? $extraEntityParameters : [$extraEntityParameters]);
            $parametersMerged = array_merge($parameters, $entityParameters);

            if (empty($parametersMerged)) continue;
            $fileReferenceParameters[$entityClass::getEntityName()] = $parametersMerged;
        }

        return $fileReferenceParameters;
    }

}
