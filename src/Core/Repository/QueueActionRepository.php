<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Model\QueueAction;

interface QueueActionRepository
{
    function save(QueueAction ...$actions): void;

    function getLastAction(string|int $userOwnerId): ?QueueAction;

    /**
     * @param string|int $userOwnerId
     * @param int|null $afterTimestamp
     * @param int[] $filterDateTimes
     * @return QueueAction[]
     */
    function getActions(string|int $userOwnerId, ?int $afterTimestamp = null, array $filterDateTimes = []): array;
}