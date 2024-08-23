<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Repository\QueueActionRepository;

class GetQueueActions
{
    function __construct(
        private QueueActionRepository $queueActionRepository
    )
    {

    }

    function __invoke(UserAuth $userAuth, ?int $afterTimestamp = null, array $excludeDateTimes = []): array
    {
        $actions = $this->queueActionRepository->getActions($userAuth->userId, $afterTimestamp, $excludeDateTimes);

        return array_map(function ($action) {
            return [
                'action' => $action->action->name,
                'entity' => $action->entity,
                'data' => $action->operation->toArray(),
                'actioned_at' => $action->actionedAt->getTimestamp(),
                'synced_at' => $action->syncedAt->getTimestamp(),
            ];
        }, $actions);
    }
}