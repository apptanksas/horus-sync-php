<?php

namespace AppTank\Horus\Client;

use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Mapper\QueueActionMapper;
use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Core\Transaction\ITransactionHandler;
use AppTank\Horus\Core\Validator\EntityRestrictionValidator;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;

/**
 * Class HorusQueueActionClient
 *
 * Responsible for handling queue actions and synchronizing entity operations
 * within a transactional context. It validates entity restrictions,
 * organizes operations by type, and persists both entities and their
 * associated queue actions.
 *
 * @package App\Clients
 */
class HorusQueueActionClient implements IHorusQueueActionClient
{
    /**
     * HorusQueueActionClient constructor.
     *
     * @param ITransactionHandler $transactionHandler Handler for wrapping operations in a transaction.
     * @param QueueActionRepository $queueActionRepository Repository for queue action persistence.
     * @param EntityRepository $entityRepository Repository for entity CRUD operations.
     * @param Config $config Application configuration.
     */
    private EntityRestrictionValidator $entityRestrictionValidator;

    function __construct(
        private readonly ITransactionHandler   $transactionHandler,
        private readonly QueueActionRepository $queueActionRepository,
        private readonly EntityRepository      $entityRepository,
        private readonly Config                $config,
    )
    {
        $this->entityRestrictionValidator = new EntityRestrictionValidator(
            $this->entityRepository,
            $this->config
        );
    }

    /**
     * Pushes a batch of QueueAction objects, applying them to entities
     * in chronological order and persisting both entities and actions.
     * All operations are executed within a single transaction.
     *
     * @param QueueAction ...$actions Actions to be processed.
     *
     * @return void
     */
    public function pushActions(QueueAction ...$actions): void
    {
        // Sort actions by timestamp to ensure chronological processing
        usort($actions, fn(QueueAction $a, QueueAction $b) => $a->actionedAt <=> $b->actionedAt);

        $this->transactionHandler->executeTransaction(function () use ($actions) {
            // Re-sort inside transaction for safety
            usort($actions, fn(QueueAction $a, QueueAction $b) => $a->actionedAt <=> $b->actionedAt);

            // Separate actions by operation type
            [$insertActions, $updateActions, $deleteActions] = $this->organizeActions(...$actions);

            // Extract entities for insert actions
            $insertEntities = array_map(fn(QueueAction $action) => $action->operation, $insertActions);
            $deleteEntities = array_map(fn(QueueAction $action) => $action->operation, $deleteActions);
            $insertGrouped = $this->groupEntityByUserOwnerId(...$insertEntities);
            $deleteGrouped = $this->groupEntityByUserOwnerId(...$deleteEntities);

            // Validate restrictions per user owner
            foreach ($insertGrouped as $userOwnerId => $insertOperations) {
                $this->entityRestrictionValidator->validateInsertEntityRestrictions($userOwnerId, $insertOperations, $deleteGrouped[$userOwnerId] ?? []);
            }

            // Apply CRUD operations to entities
            $this->entityRepository->insert(...$insertEntities);
            $this->entityRepository->update(...array_map(fn(QueueAction $action) => $action->operation, $updateActions));
            $this->entityRepository->delete(...$deleteEntities);

            // Persist queue actions
            $this->queueActionRepository->save(...$actions);
        });
    }

    /**
     * Retrieves the most recent queue action for a given entity and action type.
     *
     * @param SyncAction $action The sync action enum value to filter by.
     * @param string $entityName Name of the entity.
     * @param string $entityId Identifier of the entity instance.
     *
     * @return QueueAction|null Latest QueueAction if found; otherwise null.
     */
    public function getLastActionByEntity(SyncAction $action, string $entityName, string $entityId): ?QueueAction
    {
        $record = SyncQueueActionModel::query()
            ->where(SyncQueueActionModel::ATTR_ENTITY, $entityName)
            ->where(SyncQueueActionModel::ATTR_ENTITY_ID, $entityId)
            ->where(SyncQueueActionModel::ATTR_ACTION, $action->value())
            ->orderByDesc('id')
            ->limit(1)
            ->first();

        if ($record === null) {
            return null;
        }

        return QueueActionMapper::createFromEloquent($record);
    }

    /**
     * Organizes a list of QueueAction objects into insert, update, and delete categories.
     *
     * @param QueueAction ...$actions List of actions to organize.
     *
     * @return array{0: QueueAction[], 1: QueueAction[], 2: QueueAction[]} Array containing
     *         insert, update, and delete action lists respectively.
     */
    private function organizeActions(QueueAction ...$actions): array
    {
        $insertActions = [];
        $updateActions = [];
        $deleteActions = [];

        foreach ($actions as $action) {
            switch (true) {
                case $action->operation instanceof EntityInsert:
                    $insertActions[] = $action;
                    break;
                case $action->operation instanceof EntityUpdate:
                    $updateActions[] = $action;
                    break;
                case $action->operation instanceof EntityDelete:
                    $deleteActions[] = $action;
                    break;
            }
        }

        return [$insertActions, $updateActions, $deleteActions];
    }

    /**
     * Groups entities by their owner user ID for restriction validation.
     *
     * @param EntityOperation ...$entities Entities to group.
     *
     * @return array<string, EntityOperation[]> Entities keyed by owner ID.
     */
    private function groupEntityByUserOwnerId(EntityOperation ...$entities): array
    {
        $grouped = [];

        foreach ($entities as $entity) {
            $ownerId = $entity->ownerId;
            $grouped[$ownerId][] = $entity;
        }

        return $grouped;
    }
}
