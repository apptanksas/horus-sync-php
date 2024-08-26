<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Repository\QueueActionRepository;

/**
 * @internal Class GetQueueLastAction
 *
 * Retrieves the last action from the queue for the authenticated user.
 * This class interacts with the QueueActionRepository to obtain the last action.
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class GetQueueLastAction
{
    /**
     * GetQueueLastAction constructor.
     *
     * @param QueueActionRepository $queueActionRepository Repository for accessing queue actions.
     */
    function __construct(
        private QueueActionRepository $queueActionRepository
    )
    {

    }

    /**
     * Invokes the GetQueueLastAction class to retrieve the last action from the queue.
     *
     * Fetches the most recent action from the queue for the authenticated user and formats it into an array.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @return array An array containing details of the last queue action.
     */
    function __invoke(UserAuth $userAuth): array
    {
        $action = $this->queueActionRepository->getLastAction($userAuth->getEffectiveUserId());

        return [
            'action' => $action->action->name,
            'entity' => $action->entity,
            'data' => $action->operation->toArray(),
            'actioned_at' => $action->actionedAt->getTimestamp(),
            'synced_at' => $action->syncedAt->getTimestamp(),
        ];
    }
}