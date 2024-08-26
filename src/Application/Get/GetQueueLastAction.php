<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Repository\QueueActionRepository;

class GetQueueLastAction
{
    function __construct(
        private QueueActionRepository $queueActionRepository
    )
    {

    }

    function __invoke(string|int $userOwnerId): array
    {
        $action = $this->queueActionRepository->getLastAction($userOwnerId);

        return [
            'action' => $action->action->name,
            'entity' => $action->entity,
            'data' => $action->operation->toArray(),
            'actioned_at' => $action->actionedAt->getTimestamp(),
            'synced_at' => $action->syncedAt->getTimestamp(),
        ];
    }
}