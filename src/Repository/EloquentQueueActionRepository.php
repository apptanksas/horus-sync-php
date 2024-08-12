<?php

namespace AppTank\Horus\Repository;

use App\Models\SyncActionQueue;
use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use Illuminate\Support\Facades\DB;

readonly class EloquentQueueActionRepository implements QueueActionRepository
{

    public function __construct(
        private ?string $connectionName = null,
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
        $actionedAt = $model->getActionedAt()->toDateTimeImmutable();

        match ($action) {
            SyncAction::INSERT => $operation = EntityOperationFactory::createEntityInsert($model->getOwnerId(), $model->getEntity(), $model->getData(), $actionedAt),
            SyncAction::UPDATE => $operation = EntityOperationFactory::createEntityUpdate($model->getOwnerId(), $model->getEntity(), $model->getData()["id"], $model->getData()["attributes"], $actionedAt),
            SyncAction::DELETE => $operation = EntityOperationFactory::createEntityDelete($model->getOwnerId(), $model->getEntity(), $model->getData()["id"], $actionedAt),
        };

        return new QueueAction(
            SyncAction::newInstance($model->getAction()),
            $model->getEntity(),
            $operation,
            $model->getActionedAt()->toDateTimeImmutable(),
            $model->getSyncedAt()->toDateTimeImmutable(),
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
}