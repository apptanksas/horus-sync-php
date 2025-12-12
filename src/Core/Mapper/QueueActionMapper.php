<?php

namespace AppTank\Horus\Core\Mapper;

use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;

class QueueActionMapper
{
    public static function createFromEloquent(SyncQueueActionModel $model): QueueAction
    {
        $action = SyncAction::newInstance($model->getAction());
        $actionedAt = $model->getActionedAt();

        match ($action) {
            SyncAction::INSERT => $operation = EntityOperationFactory::createEntityInsert($model->getOwnerId(), $model->getEntity(), $model->getData(), $actionedAt),
            SyncAction::UPDATE, SyncAction::MOVE => $operation = EntityOperationFactory::createEntityUpdate($model->getOwnerId(), $model->getEntity(), $model->getData()["id"], $model->getData()["attributes"], $actionedAt),
            SyncAction::DELETE => $operation = EntityOperationFactory::createEntityDelete($model->getOwnerId(), $model->getEntity(), $model->getData()["id"], $actionedAt),
        };

        return new QueueAction(
            SyncAction::newInstance($model->getAction()),
            $model->getEntity(),
            $model->getEntityId(),
            $operation,
            $model->getActionedAt(),
            $model->getSyncedAt(),
            $model->getUserId(),
            $model->getOwnerId(),
            bySystem: false,
            sequence: $model->getId()
        );
    }
}