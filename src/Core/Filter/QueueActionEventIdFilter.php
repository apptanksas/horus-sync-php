<?php

namespace AppTank\Horus\Core\Filter;

use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\QueueActionRepository;

/**
 * Filters queue actions whose event IDs are not registered.
 */
final readonly class QueueActionEventIdFilter
{
    public function __construct(
        private QueueActionRepository $queueActionRepository
    ) {
    }

    /**
     * Keeps unregistered actions and actions without an event ID for backwards compatibility.
     *
     * @param QueueAction ...$actions The queue actions to filter.
     * @return QueueAction[] The unregistered actions and legacy actions without an event ID.
     */
    public function filter(QueueAction ...$actions): array
    {
        $eventIds = array_values(array_filter(
            array_map(fn(QueueAction $action) => $action->eventId, $actions),
            fn(?string $eventId) => $eventId !== null
        ));

        if (empty($eventIds)) {
            return $actions;
        }

        $registeredEventIds = $this->queueActionRepository->checkExistsByEventIds($eventIds);

        return array_values(array_filter(
            $actions,
            fn(QueueAction $action) => $action->eventId === null || !($registeredEventIds[$action->eventId] ?? false)
        ));
    }
}