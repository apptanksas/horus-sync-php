<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;

/**
 * @internal Class GetQueueLastAction
 *
 * Retrieves the last action from the queue for the authenticated user.
 * This class interacts with the QueueActionRepository to obtain the last action, taking into account
 * not only the entities owned by the authenticated user but also the entities owned by other users
 * that have granted access to the authenticated user. The action is validated to ensure the
 * authenticated user really has access to the associated entity, in the same way as `GetQueueActions`.
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class GetQueueLastAction
{
    /**
     * Number of actions retrieved per batch while scanning backwards for an accessible action.
     */
    private const int BATCH_SIZE = 50;

    /**
     * GetQueueLastAction constructor.
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
     * Invokes the GetQueueLastAction class to retrieve the last action from the queue.
     *
     * Fetches the most recent action from the queue that the authenticated user has access to
     * -either because they own it or because one of the owners that granted them access owns it-,
     * and formats it into an array.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @return array An array containing details of the last queue action, or an empty array if there is
     *  no action the authenticated user has access to.
     */
    function __invoke(UserAuth $userAuth): array
    {
        $action = $this->findLastAccessibleAction($userAuth);

        if (is_null($action)) {
            return [];
        }

        return [
            'action' => $action->action->name,
            'entity' => $action->entity,
            'data' => $action->operation->toArray(),
            'actioned_at' => $action->actionedAt->getTimestamp(),
            'synced_at' => $action->syncedAt->getTimestamp(),
            'event_id' => $action->eventId
        ];
    }

    /**
     * Scans the queue actions -from newest to oldest- among the authenticated user's own actions and
     * those of the owners who granted them access, until an action that the user can actually access is found.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @return QueueAction|null The last accessible queue action, or null if none is found.
     */
    private function findLastAccessibleAction(UserAuth $userAuth): ?QueueAction
    {
        $ownerIds = array_unique(array_merge([$userAuth->getEffectiveUserId()], $userAuth->getUserOwnersId()));
        $beforeSequence = null;

        do {
            $actions = $this->queueActionRepository->getLastActionsByOwners($ownerIds, self::BATCH_SIZE, $beforeSequence);

            if (empty($actions)) {
                return null;
            }

            foreach ($actions as $action) {
                if ($this->canAccessAction($userAuth, $action)) {
                    return $action;
                }
            }

            $beforeSequence = end($actions)->sequence;
        } while (count($actions) === self::BATCH_SIZE);

        return null;
    }

    /**
     * Validates that the authenticated user has read access to the entity associated with the action.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @param QueueAction $action The action whose entity access is being validated.
     * @return bool True if the user can access the entity, otherwise false.
     */
    private function canAccessAction(UserAuth $userAuth, QueueAction $action): bool
    {
        // Validate is primary entity and has read permission
        if ($this->entityMapper->isPrimaryEntity($action->entity) && $userAuth->hasGranted($action->entity, $action->entityId, Permission::READ)) {
            return true;
        }

        // Validate if the user owner is user authenticated
        if ($action->userId && $action->userId === $userAuth->userId) {
            return true;
        }

        return $this->accessValidatorRepository->canAccessEntity($userAuth, new EntityReference($action->entity, $action->entityId), Permission::READ);
    }
}
