<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\QueueActionRepository;
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
}