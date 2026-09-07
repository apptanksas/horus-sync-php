<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Mapper\QueueActionMapper;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\Util\IDateTimeUtil;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @internal Class EloquentQueueActionRepository
 *
 * EloquentQueueActionRepository implements the QueueActionRepository interface using Eloquent ORM.
 * It handles saving, retrieving, and building queue actions in the database.
 */
readonly class EloquentQueueActionRepository implements QueueActionRepository
{
    public function __construct(
        private IDateTimeUtil $dateTimeUtil,
        private ?string       $connectionName = null,
    )
    {
    }

    /**
     * Saves one or more queue actions to the database.
     *
     * This method inserts or updates the provided queue actions in the database.
     * If an action has an event_id, it uses updateOrInsert based on event_id.
     * If event_id is null, it inserts a new record to maintain backwards compatibility.
     * Throws an exception if the operation fails.
     *
     * @param QueueAction ...$actions The queue actions to be saved.
     * @throws \Exception If the operation fails.
     */
    function save(QueueAction ...$actions): void
    {
        usort($actions, fn(QueueAction $a, QueueAction $b) => $a->actionedAt->getTimestamp() <=> $b->actionedAt->getTimestamp());

        $dataWithEventIds = [];
        $dataDefault = [];
        $table = $this->getTable();

        foreach ($actions as $action) {
            // With event ids
            if (!is_null($action->eventId) && !empty($action->eventId)) {
                $dataWithEventIds[] = $this->parseData($action);
                continue;
            }

            $dataDefault[] = $this->parseData($action);
        }

        if (!empty($dataDefault) && !$table->insert($dataDefault)) {
            throw new \Exception('Failed to save queue actions');
        }

        if (!empty($dataWithEventIds) && !$table->upsert($dataWithEventIds, [SyncQueueActionModel::ATTR_EVENT_ID])) {
            throw new \Exception('Failed to save queue actions');
        }
    }

    /**
     * Retrieves the most recent queue action for a specific user or owner ID.
     *
     * @param int|string $userOwnerId The ID of the user or owner to retrieve the last action for.
     * @return QueueAction|null The most recent QueueAction, or null if no actions are found.
     */
    function getLastAction(int|string $userOwnerId): ?QueueAction
    {
        $result = SyncQueueActionModel::query()
            ->where(SyncQueueActionModel::FK_OWNER_ID, $userOwnerId)
            ->where(SyncQueueActionModel::ATTR_SKIPPED, false)
            ->orderByDesc("id")->limit(1)->get()->first();

        if (is_null($result)) {
            return null;
        }

        return $this->buildQueueActionByModel($result);
    }

    /**
     * Retrieves actions combining restricted owners (filtered by date or event ID) and unrestricted owners (always included).
     *
     * @param array|int|string $filteredOwnerIds Owners subject to the date/event exclusion logic.
     * @param int|null $afterTimestamp Global time filter (applies to everything).
     * @param array $excludeDateTimes Dates to exclude for the filtered owners.
     * @param array $alwaysIncludeOwnerIds Owners whose actions are always retrieved (ignoring exclusions).
     * @param string|null $afterEventId Filter actions after the specified event ID.
     * @param array $excludeEventIds Event IDs to exclude for the filtered owners.
     */
    public function getActions(
        array|int|string $filteredOwnerIds,
        ?int             $afterTimestamp = null,
        array            $excludeDateTimes = [],
        array            $alwaysIncludeOwnerIds = [],
        ?string          $afterEventId = null,
        array            $excludeEventIds = [],
    ): array
    {

        $query = SyncQueueActionModel::query()->where(SyncQueueActionModel::ATTR_SKIPPED, false);

        // 1. Global Time Filter (Applies to both groups)
        if ($afterTimestamp !== null) {
            $formattedAfterDate = $this->dateTimeUtil->getFormatDate($this->dateTimeUtil->parseDatetime($afterTimestamp)->getTimestamp());
            $query->where(SyncQueueActionModel::ATTR_SYNCED_AT, '>=', $formattedAfterDate)->orderBy("id");
        }

        // Global Event ID Filter (Applies to both groups)
        if ($afterEventId !== null) {
            $afterAction = SyncQueueActionModel::query()->where(SyncQueueActionModel::ATTR_EVENT_ID, $afterEventId)->first();

            if ($afterAction) {
                $query->where('id', '>', $afterAction->id)->orderBy("id");
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Prepare exclusion dates
        $arrayDateExcludes = [];
        if (!empty($excludeDateTimes)) {
            $arrayDateExcludes = array_map(
                fn($ts) => $this->dateTimeUtil->getFormatDate($this->dateTimeUtil->parseDatetime($ts)->getTimestamp()),
                $excludeDateTimes
            );
        }

        // Normalize inputs to arrays
        $filteredIds = is_array($filteredOwnerIds) ? $filteredOwnerIds : [$filteredOwnerIds];
        $unrestrictedIds = is_array($alwaysIncludeOwnerIds) ? $alwaysIncludeOwnerIds : [$alwaysIncludeOwnerIds];

        // 2. Core Logic: (Group A: Filtered) OR (Group B: Unrestricted)
        $query->where(function ($mainQuery) use ($filteredIds, $unrestrictedIds, $arrayDateExcludes, $excludeEventIds) {

            // GROUP A: The owners subject to date exclusion logic
            if (!empty($filteredIds)) {
                $mainQuery->where(function ($q) use ($filteredIds, $arrayDateExcludes, $excludeEventIds) {
                    $q->whereIn(SyncQueueActionModel::FK_OWNER_ID, $filteredIds);

                    // Apply exclusion logic ONLY to this group
                    if (!empty($arrayDateExcludes)) {
                        $q->where(function ($subQ) use ($arrayDateExcludes) {
                            // Keep if date is valid OR actor is not the owner
                            $subQ->whereNotIn(SyncQueueActionModel::ATTR_ACTIONED_AT, $arrayDateExcludes)
                                ->orWhereColumn(SyncQueueActionModel::FK_USER_ID, '!=', SyncQueueActionModel::FK_OWNER_ID);
                        });
                    }

                    // Apply event ID exclusion logic ONLY to this group
                    if (!empty($excludeEventIds)) {
                        $q->where(function ($subQ) use ($excludeEventIds) {
                            // Keep if event_id is not in excludeEventIds (or null) OR actor is not the owner
                            $subQ->where(function ($innerQ) use ($excludeEventIds) {
                                $innerQ->whereNotIn(SyncQueueActionModel::ATTR_EVENT_ID, $excludeEventIds)
                                    ->orWhereNull(SyncQueueActionModel::ATTR_EVENT_ID);
                            })->orWhereColumn(SyncQueueActionModel::FK_USER_ID, '!=', SyncQueueActionModel::FK_OWNER_ID);
                        });
                    }
                });
            }

            // GROUP B: The owners that are ALWAYS included (e.g., Auth user)
            if (!empty($unrestrictedIds)) {
                // "OR include these owners unconditionally"
                $mainQuery->orWhereIn(SyncQueueActionModel::FK_OWNER_ID, $unrestrictedIds);
            }
        });

        return $query->get()
            ->map(fn(SyncQueueActionModel $model) => $this->buildQueueActionByModel($model))
            ->toArray();
    }

    /**
     * Checks if actions are registered for the given event IDs.
     *
     * @param array $eventIds List of event IDs to check.
     * @return array Associative array mapping each event ID to a boolean indicating if it is registered.
     */
    public function checkExistsByEventIds(array $eventIds): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $validEventIds = array_values(array_filter($eventIds, fn($id) => is_string($id) && trim($id) !== ''));

        $registered = [];
        if (!empty($validEventIds)) {
            $registered = $this->getTable()
                ->whereIn(SyncQueueActionModel::ATTR_EVENT_ID, $validEventIds)
                ->pluck(SyncQueueActionModel::ATTR_EVENT_ID)
                ->toArray();
        }

        $registeredMap = array_fill_keys($registered, true);

        $result = [];
        foreach ($eventIds as $eventId) {
            $result[$eventId] = isset($registeredMap[$eventId]);
        }

        return $result;
    }


    /**
     * Converts a QueueAction object to an array format suitable for database insertion.
     *
     * @param QueueAction $queueAction The QueueAction object to convert.
     * @return array The array representation of the queue action.
     */
    private function parseData(QueueAction $queueAction): array
    {
        return [
            SyncQueueActionModel::ATTR_ACTION => $queueAction->action->value,
            SyncQueueActionModel::ATTR_ENTITY => $queueAction->entity,
            SyncQueueActionModel::ATTR_ENTITY_ID => $queueAction->operation->id,
            SyncQueueActionModel::ATTR_DATA => json_encode($queueAction->operation->toArray()),
            SyncQueueActionModel::ATTR_ACTIONED_AT => $queueAction->actionedAt,
            SyncQueueActionModel::ATTR_SYNCED_AT => $queueAction->syncedAt,
            SyncQueueActionModel::FK_USER_ID => $queueAction->userId,
            SyncQueueActionModel::FK_OWNER_ID => $queueAction->ownerId,
            SyncQueueActionModel::ATTR_BY_SYSTEM => $queueAction->bySystem,
            SyncQueueActionModel::ATTR_SKIPPED => $queueAction->skipped,
            SyncQueueActionModel::ATTR_EVENT_ID => $queueAction->eventId,
        ];
    }

    /**
     * Builds a QueueAction object from a SyncQueueActionModel instance.
     *
     * @param SyncQueueActionModel $model The model instance to build the QueueAction from.
     * @return QueueAction The constructed QueueAction object.
     */
    private function buildQueueActionByModel(SyncQueueActionModel $model): QueueAction
    {
        return QueueActionMapper::createFromEloquent($model);
    }

    /**
     * Retrieves a query builder instance for the sync queue actions table.
     *
     * @return Builder
     */
    private function getTable(): Builder
    {
        return (is_null($this->connectionName)) ? DB::table(SyncQueueActionModel::TABLE_NAME) :
            DB::connection($this->connectionName)->table(SyncQueueActionModel::TABLE_NAME);
    }
}