<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Repository\QueueActionRepository;

/**
 * @internal Class GetQueueActions
 *
 * Retrieves and formats queue actions for the authenticated user.
 * This class interacts with the QueueActionRepository to obtain the actions.
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class GetQueueActions
{
    /**
     * GetQueueActions constructor.
     *
     * @param QueueActionRepository $queueActionRepository Repository for accessing queue actions.
     */
    function __construct(
        private QueueActionRepository $queueActionRepository
    )
    {

    }

    /**
     * Invokes the GetQueueActions class to retrieve and format queue actions.
     *
     * Fetches actions from the queue for the authenticated user and formats them into an array.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @param int|null $afterTimestamp Optional timestamp to filter actions after a certain date.
     * @param array $excludeDateTimes Optional array of date times to exclude from the results.
     * @return array An array of formatted queue actions.
     */
    function __invoke(UserAuth $userAuth, ?int $afterTimestamp = null, array $excludeDateTimes = []): array
    {
        $actions = $this->queueActionRepository->getActions($userAuth->getEffectiveUserId(), $afterTimestamp, $excludeDateTimes);

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