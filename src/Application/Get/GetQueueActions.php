<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
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
     * @param EntityAccessValidatorRepository $accessValidatorRepository Repository for validating entity access.
     * @param EntityMapper $entityMapper Mapper for converting entities to arrays.
     */
    function __construct(
        private QueueActionRepository           $queueActionRepository,
        private EntityAccessValidatorRepository $accessValidatorRepository,
        private EntityMapper                    $entityMapper,
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
        $userIds = array_merge([$userAuth->userId], $userAuth->getUserOwnersId());

        $actions = $this->queueActionRepository->getActions($userIds, $afterTimestamp, $excludeDateTimes);

        $actionsFiltered = array_values(
            array_filter($actions, function ($action) use ($userAuth) {

                // Validate is primary entity and has read permission
                if ($this->entityMapper->isPrimaryEntity($action->entity) && $userAuth->hasGranted($action->entity, $action->entityId, Permission::READ)) {
                    return true;
                }

                // Validate if the user owner is user authenticated
                if ($action->userId && $action->userId === $userAuth->userId) {
                    return true;
                }

                return $this->accessValidatorRepository->canAccessEntity($userAuth, new EntityReference($action->entity, $action->entityId), Permission::READ);
            })
        );

        return array_map(function ($action) {
            return [
                'action' => $action->action->name,
                'entity' => $action->entity,
                'data' => $action->operation->toArray(),
                'actioned_at' => $action->actionedAt->getTimestamp(),
                'synced_at' => $action->syncedAt->getTimestamp(),
            ];
        }, $actionsFiltered);
    }
}