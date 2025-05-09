<?php

namespace AppTank\Horus\Application\Sync;

use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Bus\IEventBus;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\Restriction\MaxCountEntityRestriction;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Core\Exception\OperationNotPermittedException;
use AppTank\Horus\Core\Exception\RestrictionException;
use AppTank\Horus\Core\File\FilePathGenerator;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\File\SyncFileStatus;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Model\FileUploaded;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Core\Transaction\ITransactionHandler;
use AppTank\Horus\Core\Validator\EntityRestrictionValidator;

/**
 * @internal Class SyncQueueActions
 *
 * Handles the synchronization of queue actions, ensuring that the actions are organized, validated, and executed within a transaction.
 * This class is responsible for organizing the actions into insert, update, and delete operations, performing the respective database operations,
 * saving the queue actions, and publishing events related to the sync actions.
 *
 * @author John Ospina
 * Year: 2024
 */
class SyncQueueActions
{
    private FilePathGenerator $filePathGenerator;
    private EntityRestrictionValidator $entityRestrictionValidator;


    /**
     * SyncQueueActions constructor.
     *
     * @param ITransactionHandler $transactionHandler Handler for managing database transactions.
     * @param QueueActionRepository $queueActionRepository Repository for queue actions.
     * @param EntityRepository $entityRepository Repository for managing entities.
     * @param EntityAccessValidatorRepository $accessValidatorRepository Repository for validating access to entities.
     * @param FileUploadedRepository $fileUploadedRepository Repository for managing file uploads.
     * @param IEventBus $eventBus Event bus for dispatching events.
     * @param IFileHandler $fileHandler File handler for managing file uploads.
     * @param EntityMapper $entityMapper Mapper for entity classes.
     */
    function __construct(
        private readonly ITransactionHandler             $transactionHandler,
        private readonly QueueActionRepository           $queueActionRepository,
        private readonly EntityRepository                $entityRepository,
        private readonly EntityAccessValidatorRepository $accessValidatorRepository,
        private readonly FileUploadedRepository          $fileUploadedRepository,
        private readonly IEventBus                       $eventBus,
        private readonly IFileHandler                    $fileHandler,
        private readonly EntityMapper                    $entityMapper,
        private readonly Config                          $config
    )
    {
        $this->filePathGenerator = new FilePathGenerator($this->entityRepository, $this->config);
        $this->entityRestrictionValidator = new EntityRestrictionValidator($this->entityRepository, $this->config);

    }

    /**
     * Invokes the SyncQueueActions class to process and synchronize the queue actions.
     *
     * The actions are first sorted by the time they were actioned. Then, they are organized into insert, update,
     * and delete actions. These actions are executed within a transaction to ensure data integrity.
     * After executing the actions, the events related to these actions are published via the event bus.
     *
     * @param UserAuth $userAuth The authenticated user performing the actions.
     * @param QueueAction ...$actions The queue actions to be synchronized.
     * @return void
     *
     * @throws OperationNotPermittedException If the user does not have the necessary permissions to perform an action.
     */
    function __invoke(UserAuth $userAuth, QueueAction ...$actions): void
    {
        usort($actions, fn(QueueAction $a, QueueAction $b) => $a->actionedAt <=> $b->actionedAt);

        $this->transactionHandler->executeTransaction(function () use ($actions, $userAuth) {
            usort($actions, fn(QueueAction $a, QueueAction $b) => $a->actionedAt <=> $b->actionedAt);

            [$insertActions, $updateActions, $deleteActions] = $this->organizeActions($userAuth, ...$actions);

            $insertEntities = array_map(fn(QueueAction $action) => $action->operation, $insertActions);

            $this->entityRestrictionValidator->validateInsertEntityRestrictions($userAuth->getEffectiveUserId(), $insertEntities);

            $this->entityRepository->insert(...$insertEntities);
            $this->entityRepository->update(...array_map(fn(QueueAction $action) => $action->operation, $updateActions));
            $this->entityRepository->delete(...array_map(fn(QueueAction $action) => $action->operation, $deleteActions));
            $this->queueActionRepository->save(...$actions);

            $this->validateFilesUploaded($userAuth, $insertEntities);

            $this->publishEvents($actions);
        });
    }

    /**
     * Dispatches events for the actions based on their type (insert, update, delete).
     *
     * @param QueueAction[] $actions The actions for which to publish events.
     * @return void
     */
    private function publishEvents(array $actions): void
    {
        foreach ($actions as $action) {

            $eventData = [
                $action->entity,
                $action->operation->toArray()
            ];

            if ($action->action == SyncAction::INSERT) {
                $this->eventBus->publish("sync.insert", $eventData);
            } elseif ($action->action == SyncAction::UPDATE) {
                $this->eventBus->publish("sync.update", $eventData);
            } elseif ($action->action == SyncAction::DELETE) {
                $this->eventBus->publish("sync.delete", $eventData);
            }
        }
    }

    /**
     * Organizes the actions into insert, update, and delete categories.
     *
     * @param UserAuth $userAuth The authenticated user performing the actions.
     * @param QueueAction ...$actions The actions to organize.
     * @return array[] An array containing the organized actions [insertActions, updateActions, deleteActions].
     *
     * @throws OperationNotPermittedException If the user does not have the necessary permissions to update or delete an entity.
     */
    private function organizeActions(UserAuth $userAuth, QueueAction ...$actions): array
    {
        $insertActions = [];
        $updateActions = [];
        $deleteActions = [];

        foreach ($actions as $action) {

            $entityReference = new EntityReference($action->entity, $action->operation->id);

            // Check if the update about entity is not on a new entity
            $isEntityIdtInInsertActions = in_array($action->operation->id, array_map(fn(QueueAction $action) => $action->operation->id, $insertActions));

            if ($action->operation instanceof EntityInsert) {
                $insertActions[] = $action;
            } elseif ($action->operation instanceof EntityUpdate) {

                // Check if user has permission to update entity
                if ($isEntityIdtInInsertActions === false && $this->accessValidatorRepository->canAccessEntity($userAuth, $entityReference, Permission::UPDATE) === false) {
                    throw new OperationNotPermittedException("No have access to update entity {$action->entity} with id {$action->operation->id}");
                }

                $updateActions[] = $action;

            } elseif ($action->operation instanceof EntityDelete) {

                // Check if user has permission to delete entity
                if ($isEntityIdtInInsertActions === false && $this->accessValidatorRepository->canAccessEntity($userAuth, $entityReference, Permission::DELETE) === false) {
                    throw new OperationNotPermittedException("No have access to delete entity {$action->entity} with id {$action->operation->id}");
                }

                $deleteActions[] = $action;
            }
        }

        return [$insertActions, $updateActions, $deleteActions];
    }


    /**
     * Validates the files uploaded in the insert operations. Moves the files to the correct path and updates the file reference.
     *
     * @param EntityInsert[] $operations
     * @return void
     */
    private function validateFilesUploaded(UserAuth $userAuth, array $operations): void
    {
        foreach ($operations as $operation) {

            foreach ($operation->data as $key => $value) {

                $parametersReferenceFile = $this->entityMapper->getParametersReferenceFile($operation->entity);

                if (in_array($key, $parametersReferenceFile)) {
                    $referenceFile = $value;
                    $fileUploaded = $this->fileUploadedRepository->search($referenceFile);

                    if (is_null($fileUploaded)) {
                        throw new \Exception("File not found");
                    }

                    $pathFileDestination = $this->filePathGenerator->create($userAuth, new EntityReference($operation->entity, $operation->id)) . basename($fileUploaded->path);
                    $urlFile = $this->fileHandler->generateUrl($pathFileDestination);

                    if ($this->fileHandler->copy($fileUploaded->path, $pathFileDestination)) {
                        $this->fileHandler->delete($fileUploaded->path);
                    } else {
                        throw new \Exception("Error copying file");
                    }

                    $fileUploaded = new FileUploaded(
                        $fileUploaded->id,
                        $fileUploaded->mimeType,
                        $pathFileDestination,
                        $urlFile,
                        $fileUploaded->ownerId,
                        SyncFileStatus::LINKED
                    );

                    $this->fileUploadedRepository->save($fileUploaded);
                }

            }
        }
    }

}