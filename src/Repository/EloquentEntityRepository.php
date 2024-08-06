<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Util\IDateTimeUtil;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use Illuminate\Support\Facades\DB;

readonly class EloquentEntityRepository implements EntityRepository
{

    public function __construct(
        private EntityMapper $entityMapper,
        private IDateTimeUtil $dateTimeUtil,
        private ?string      $connectionName = null,
    )
    {

    }

    function insert(EntityInsert ...$operations): void
    {

        $groupOperationByEntity = [];

        foreach ($operations as $operation) {
            $groupOperationByEntity[$operation->entity][] = array_merge([
                EntitySynchronizable::ATTR_SYNC_HASH => $operation->hash(),
                EntitySynchronizable::ATTR_SYNC_OWNER_ID => $operation->ownerId,
                EntitySynchronizable::ATTR_SYNC_CREATED_AT => $this->dateTimeUtil->getCurrent(),
                EntitySynchronizable::ATTR_SYNC_UPDATED_AT => $this->dateTimeUtil->getCurrent(),
            ], $operation->toArray());
        }

        foreach ($groupOperationByEntity as $entity => $operations) {

            /**
             * @var EntitySynchronizable $entityClass
             */
            $entityClass = $this->entityMapper->getEloquentClassEntity($entity);
            $tableName = $entityClass::getTableName();

            $table = (is_null($this->connectionName)) ? DB::table($tableName) :
                DB::connection($this->connectionName)->table($tableName);

            if (!$table->insert($operations)) {
                throw new \Exception('Failed to insert entities');
            }
        }
    }

    function update(EntityUpdate ...$operations): void
    {
        // TODO: Implement update() method.
    }

    function delete(EntityUpdate ...$operations): void
    {
        // TODO: Implement delete() method.
    }
}