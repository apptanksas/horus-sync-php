<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Core\Util\IDateTimeUtil;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

readonly class EloquentQueueActionRepository implements QueueActionRepository
{

    public function __construct(
        private IDateTimeUtil $dateTimeUtil,
        private ?string       $connectionName = null,
    )
    {

    }

    /**
     * @throws \Exception
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


    private function parseData(QueueAction $queueAction): array
    {
        return [
            SyncQueueActionModel::ATTR_ACTION => $queueAction->action->value,
            SyncQueueActionModel::ATTR_ENTITY => $queueAction->entity,
            SyncQueueActionModel::ATTR_DATA => json_encode($queueAction->operation->toArray()),
            SyncQueueActionModel::ATTR_ACTIONED_AT => $queueAction->actionedAt,
            SyncQueueActionModel::ATTR_SYNCED_AT => $queueAction->syncedAt,
            SyncQueueActionModel::FK_USER_ID => $queueAction->userId,
            SyncQueueActionModel::FK_OWNER_ID => $queueAction->ownerId
        ];
    }

    private function buildQueueActionByModel(SyncQueueActionModel $model): QueueAction
    {
        $action = SyncAction::newInstance($model->getAction());
        $actionedAt = $model->getActionedAt();

        match ($action) {
            SyncAction::INSERT => $operation = EntityOperationFactory::createEntityInsert($model->getOwnerId(), $model->getEntity(), $model->getData(), $actionedAt),
            SyncAction::UPDATE => $operation = EntityOperationFactory::createEntityUpdate($model->getOwnerId(), $model->getEntity(), $model->getData()["id"], $model->getData()["attributes"], $actionedAt),
            SyncAction::DELETE => $operation = EntityOperationFactory::createEntityDelete($model->getOwnerId(), $model->getEntity(), $model->getData()["id"], $actionedAt),
        };

        return new QueueAction(
            SyncAction::newInstance($model->getAction()),
            $model->getEntity(),
            $operation,
            $model->getActionedAt(),
            $model->getSyncedAt(),
            $model->getUserId(),
            $model->getOwnerId()
        );
    }


    /**
     * @param int|string $userOwnerId
     * @return QueueAction[]
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
     * @param int|string $userOwnerId
     * @param int|null $afterTimestamp
     * @param array $excludeDateTimes Filter the actions that have the same actioned_at timestamp
     * @return QueueAction[]
     */
    function getActions(int|string $userOwnerId, ?int $afterTimestamp = null, array $excludeDateTimes = []): array
    {
        $query = SyncQueueActionModel::query()
            ->where(SyncQueueActionModel::FK_OWNER_ID, $userOwnerId);

        if ($afterTimestamp !== null) {
            $query = $query->where(SyncQueueActionModel::ATTR_SYNCED_AT, '>=',
                $this->dateTimeUtil->parseDatetime($afterTimestamp)->getTimestamp())->orderBy("id");
        }
        if (!empty($excludeDateTimes)) {
            $query = $query->whereNotIn(SyncQueueActionModel::ATTR_ACTIONED_AT,
                array_map(fn($timestamp) => $this->dateTimeUtil->parseDatetime($timestamp)->getTimestamp(), $excludeDateTimes));
        }

        return $query->get()->map(fn(SyncQueueActionModel $model) => $this->buildQueueActionByModel($model))->toArray();
    }
}