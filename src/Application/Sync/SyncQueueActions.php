<?php

namespace AppTank\Horus\Application\Sync;

use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Bus\IEventBus;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Exception\OperationNotPermittedException;
use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Core\Transaction\ITransactionHandler;

readonly class SyncQueueActions
{
    function __construct(
        private ITransactionHandler             $transactionHandler,
        private QueueActionRepository           $queueActionRepository,
        private EntityRepository                $entityRepository,
        private EntityAccessValidatorRepository $accessValidatorRepository,
        private IEventBus                       $eventBus,
    )
    {

    }

    function __invoke(UserAuth $userAuth, QueueAction ...$actions): void
    {
        usort($actions, fn(QueueAction $a, QueueAction $b) => $a->actionedAt <=> $b->actionedAt);

        $this->transactionHandler->executeTransaction(function () use ($actions, $userAuth) {
            usort($actions, fn(QueueAction $a, QueueAction $b) => $a->actionedAt <=> $b->actionedAt);

            [$insertActions, $updateActions, $deleteActions] = $this->organizeActions($userAuth, ...$actions);

            $this->entityRepository->insert(...array_map(fn(QueueAction $action) => $action->operation, $insertActions));
            $this->entityRepository->update(...array_map(fn(QueueAction $action) => $action->operation, $updateActions));
            $this->entityRepository->delete(...array_map(fn(QueueAction $action) => $action->operation, $deleteActions));
            $this->queueActionRepository->save(...$actions);

            $this->publishEvents($actions);
        });
    }


    /**
     * Dispatch events for the actions
     *
     * @param QueueAction[] $actions
     * @return void
     */
    private function publishEvents(array $actions): void
    {
        foreach ($actions as $action) {
            if ($action->action == SyncAction::INSERT) {
                $this->eventBus->publish("sync.insert",
                    array_merge(["entity" => $action->entity],
                        $action->operation->toArray()));
            } elseif ($action->action == SyncAction::UPDATE) {
                $this->eventBus->publish("sync.update",
                    array_merge(["entity" => $action->entity],
                        $action->operation->toArray()));
            } elseif ($action->action == SyncAction::DELETE) {
                $this->eventBus->publish("sync.delete",
                    array_merge(["entity" => $action->entity],
                        $action->operation->toArray()));
            }
        }
    }


    /**
     * Organize the actions into insert, update and delete actions
     *
     * @param UserAuth $userAuth
     * @param QueueAction ...$actions
     * @return array[]
     */
    private function organizeActions(UserAuth $userAuth, QueueAction ...$actions): array
    {
        $insertActions = [];
        $updateActions = [];
        $deleteActions = [];

        foreach ($actions as $action) {

            $entityReference = new EntityReference($action->entity, $action->operation->id);

            if ($action->operation instanceof EntityInsert) {
                $insertActions[] = $action;
            } elseif ($action->operation instanceof EntityUpdate) {

                // Check if user has permission to update entity
                if ($this->accessValidatorRepository->canAccessEntity($userAuth, $entityReference, Permission::UPDATE) === false) {
                    throw new OperationNotPermittedException("No have access to update entity {$action->entity} with id {$action->operation->id}");
                }

                $updateActions[] = $action;

            } elseif ($action->operation instanceof EntityDelete) {

                // Check if user has permission to delete entity
                if ($this->accessValidatorRepository->canAccessEntity($userAuth, $entityReference, Permission::DELETE) === false) {
                    throw new OperationNotPermittedException("No have access to delete entity {$action->entity} with id {$action->operation->id}");
                }

                $deleteActions[] = $action;
            }
        }

        return [$insertActions, $updateActions, $deleteActions];
    }

}