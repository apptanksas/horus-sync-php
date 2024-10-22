<?php

namespace AppTank\Horus\Illuminate\Console;

use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\File\SyncFileStatus;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;
use Illuminate\Console\Command;
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

    /**
     * Constructor initializes the command and assigns file handler and entity mapper instances.
     */
    public function __construct()
    {
        parent::__construct();
        $this->fileHandler = Horus::getInstance()->getFileHandler();
        $this->entityMapper = Horus::getInstance()->getEntityMapper();
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
            $expirationDays = $this->argument('expirationDays');
            $this->deleteFilesPendingExpired($expirationDays);
            $this->deleteFilesReferencedInDataDeleted($expirationDays);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return CommandAlias::FAILURE;
        }
        return CommandAlias::SUCCESS;
    }

    /**
     * Deletes files that are marked as "PENDING" and were created before the expiration period.
     * Uses the `expirationDays` to determine which files are considered expired.
     *
     * @param int $expirationDays The number of days before which files are considered expired.
     */
    private function deleteFilesPendingExpired(int $expirationDays): void
    {
        // Fetch files that are pending and created before the expiration date
        $filesUploaded = SyncFileUploadedModel::query()
            ->where(SyncFileUploadedModel::ATTR_STATUS, SyncFileStatus::PENDING->value())
            ->where(SyncFileUploadedModel::CREATED_AT, '<', now()->subDays($expirationDays)->toDateTimeString())
            ->get([SyncFileUploadedModel::ATTR_ID, SyncFileUploadedModel::ATTR_PATH]);

        // Delete each file and remove it from the database
        foreach ($filesUploaded as $fileUploaded) {
            $this->fileHandler->delete($fileUploaded->getPath());
            $fileUploaded->forceDelete();
        }

        // Log the number of deleted files
        $this->info(sprintf(self::OUTPUT_FORMAT_MESSAGE_PENDING_FILES, $filesUploaded->count()));
    }

    /**
     * Deletes files that are referenced by entities marked as deleted in the system.
     * It checks records from entities that have been soft-deleted and removes any files they reference.
     *
     * @param int $expirationDays The number of days to determine if the entity data is considered old.
     */
    private function deleteFilesReferencedInDataDeleted(int $expirationDays): void
    {
        // Retrieve all entities that could reference files
        $entities = $this->entityMapper->getEntities();

        // Iterate over each entity type
        /**
         * @var EntitySynchronizable $entityClass The entity class that can be synchronized.
         */
        foreach ($entities as $entityClass) {

            $parametersReferenceFile = $this->entityMapper->getParametersReferenceFile($entityClass::getEntityName());

            // Fetch soft-deleted records for the entity that are older than expiration days
            $recordsDeleted = $entityClass::onlyTrashed()
                ->where(EntitySynchronizable::ATTR_SYNC_DELETED_AT, '<', now()->subDays($expirationDays)->toDateTimeString())
                ->get(array_merge([$entityClass::ATTR_ID], $parametersReferenceFile));

            // For each deleted record, remove associated files
            foreach ($recordsDeleted as $recordDeleted) {
                foreach ($parametersReferenceFile as $parameterReferenceFile) {
                    $fileId = $recordDeleted->{$parameterReferenceFile};
                    $fileUploaded = SyncFileUploadedModel::query()->find($fileId);

                    // If file exists, delete it and mark as deleted in the database
                    if (!is_null($fileUploaded)) {
                        $this->fileHandler->delete($fileUploaded->getPath());
                        $fileUploaded->update([SyncFileUploadedModel::ATTR_STATUS => SyncFileStatus::DELETED->value()]);
                    }
                }
            }

            // Log the number of deleted files
            $this->info(sprintf(self::OUTPUT_FORMAT_MESSAGE_DELETED_FILES, $recordsDeleted->count(), $entityClass::getEntityName()));
        }
    }
}
