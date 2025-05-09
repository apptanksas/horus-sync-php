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

class HorusQueueActionClient
{
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

    public function pushActions(QueueAction ...$actions): void
    {
        usort($actions, fn(QueueAction $a, QueueAction $b) => $a->actionedAt <=> $b->actionedAt);

        $this->transactionHandler->executeTransaction(function () use ($actions) {
            usort($actions, fn(QueueAction $a, QueueAction $b) => $a->actionedAt <=> $b->actionedAt);

            [$insertActions, $updateActions, $deleteActions] = $this->organizeActions(...$actions);

            $insertEntities = array_map(fn(QueueAction $action) => $action->operation, $insertActions);

            foreach ($this->groupEntityByUserOwnerId(...$insertEntities) as $userOwnerId => $insertEntitiesToValidate) {
                $this->entityRestrictionValidator->validateInsertEntityRestrictions($userOwnerId, $insertEntitiesToValidate);
            }

            $this->entityRepository->insert(...$insertEntities);
            $this->entityRepository->update(...array_map(fn(QueueAction $action) => $action->operation, $updateActions));
            $this->entityRepository->delete(...array_map(fn(QueueAction $action) => $action->operation, $deleteActions));
            $this->queueActionRepository->save(...$actions);
        });
    }


    public function getLastActionByEntity(SyncAction $action, string $entityName, string $entityId): ?QueueAction
    {
        $result = SyncQueueActionModel::query()
            ->where(SyncQueueActionModel::ATTR_ENTITY, $entityName)
            ->where(SyncQueueActionModel::ATTR_ENTITY_ID, $entityId)
            ->where(SyncQueueActionModel::ATTR_ACTION, $action->value())
            ->orderByDesc("id")->limit(1)->get()->first();

        if (is_null($result)) {
            return null;
        }


        return QueueActionMapper::createFromEloquent($result);
    }

    private function organizeActions(QueueAction ...$actions): array
    {
        $insertActions = [];
        $updateActions = [];
        $deleteActions = [];

        foreach ($actions as $action) {
            if ($action->operation instanceof EntityInsert) {
                $insertActions[] = $action;
            } elseif ($action->operation instanceof EntityUpdate) {
                $updateActions[] = $action;
            } elseif ($action->operation instanceof EntityDelete) {
                $deleteActions[] = $action;
            }
        }
        return [$insertActions, $updateActions, $deleteActions];
    }

    private function groupEntityByUserOwnerId(EntityOperation ...$insertEntities): array
    {
        $output = [];

        foreach ($insertEntities as $entity) {
            $userOwnerId = $entity->ownerId;
            if (!isset($output[$userOwnerId])) {
                $output[$userOwnerId] = [];
            }
            $output[$userOwnerId][] = $entity;
        }

        return $output;
    }


}