<?php

namespace AppTank\Horus\Core\Model;

use AppTank\Horus\Core\SyncAction;

readonly class QueueAction
{
    function __construct(
        public SyncAction         $action,
        public string             $entity,
        public EntityOperation    $operation,
        public \DateTimeImmutable $actionedAt,
        public \DateTimeImmutable $syncedAt,
        public int|string         $userId,
        public int|string         $ownerId,
    )
    {

    }
}