<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Model\QueueAction;

interface QueueActionRepository
{
    function save(QueueAction ...$actions): void;

    function getLastAction(string|int $userOwnerId): ?QueueAction;
}