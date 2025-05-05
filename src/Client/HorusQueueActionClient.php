<?php

namespace AppTank\Horus\Client;

use AppTank\Horus\Core\Mapper\QueueActionMapper;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;

class HorusQueueActionClient
{
    public static function getLastActionByEntity(SyncAction $action, string $entityName, string $entityId): ?QueueAction
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

}