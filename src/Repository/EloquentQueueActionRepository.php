<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Mapper\QueueActionMapper;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\Util\IDateTimeUtil;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
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
     * This method inserts the provided queue actions into the database.
     * Throws an exception if the operation fails.
     *
     * @param QueueAction ...$actions The queue actions to be saved.
     * @throws \Exception If the insertion operation fails.
     */
    function save(QueueAction ...$actions): void
    {
        $table = (is_null($this->connectionName)) ? DB::table(SyncQueueActionModel::TABLE_NAME) :
            DB::connection($this->connectionName)->table(SyncQueueActionModel::TABLE_NAME);

        $data = [];

        foreach ($actions as $action) {
            $data[] = $this->parseData($action);
        }

        if (!$table->insert($data)) {
            throw new \Exception('Failed to save queue actions');
        }
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
     * Retrieves the most recent queue action for a specific user or owner ID.
     *
     * @param int|string $userOwnerId The ID of the user or owner to retrieve the last action for.
     * @return QueueAction|null The most recent QueueAction, or null if no actions are found.
     */
    function getLastAction(int|string $userOwnerId): ?QueueAction
    {
        $result = SyncQueueActionModel::query()->where(SyncQueueActionModel::FK_OWNER_ID, $userOwnerId)
            ->orderByDesc("id")->limit(1)->get()->first();

        if (is_null($result)) {
            return null;
        }

        return $this->buildQueueActionByModel($result);
    }

    /**
     * Retrieves a list of queue actions for a specific user or owner ID,
     * optionally filtering by timestamp and excluding specific action times.
     *
     * @param array|string|int $userOwnerIds The ID(s) of the user owner(s) whose actions are to be retrieved.
     * @param int|null $afterTimestamp Optional timestamp to filter actions after this time.
     * @param array $excludeDateTimes Optional list of timestamps to exclude from results.
     * @return QueueAction[] An array of QueueAction objects.
     */
    function getActions(int|string|array $userOwnerIds, ?int $afterTimestamp = null, array $excludeDateTimes = []): array
    {
        $query = SyncQueueActionModel::query();

        if (is_array($userOwnerIds)) {
            $query = $query->whereIn(SyncQueueActionModel::FK_OWNER_ID, $userOwnerIds);
        } else {
            $query = $query->where(SyncQueueActionModel::FK_OWNER_ID, $userOwnerIds);
        }

        if ($afterTimestamp !== null) {
            $query = $query->where(SyncQueueActionModel::ATTR_SYNCED_AT, '>=',
                $this->dateTimeUtil->getFormatDate($this->dateTimeUtil->parseDatetime($afterTimestamp)->getTimestamp()))->orderBy("id");
        }

        if (!empty($excludeDateTimes)) {
            $query = $query->whereNotIn(SyncQueueActionModel::ATTR_ACTIONED_AT,
                array_map(fn($timestamp) => $this->dateTimeUtil->getFormatDate($this->dateTimeUtil->parseDatetime($timestamp)->getTimestamp()), $excludeDateTimes));
        }

        return $query->get()->map(fn(SyncQueueActionModel $model) => $this->buildQueueActionByModel($model))->toArray();
    }
}