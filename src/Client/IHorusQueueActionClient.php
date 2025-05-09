<?php

namespace AppTank\Horus\Client;

use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\SyncAction;

interface IHorusQueueActionClient
{
    public function pushActions(QueueAction ...$actions): void;

    public function getLastActionByEntity(SyncAction $action, string $entityName, string $entityId): ?QueueAction;
}
