<?php

namespace AppTank\Horus\Repository;

use App\Models\Sync\BaseSyncModel;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Util\IDateTimeUtil;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

readonly class EloquentEntityRepository implements EntityRepository
{

    public function __construct(
        private EntityMapper  $entityMapper,
        private IDateTimeUtil $dateTimeUtil,
        private ?string       $connectionName = null,
    )
    {

    }

    /**
     * Inserts multiple entity records into the database.
     *
     * This method processes an array of EntityInsert operations, groups them by entity type,
     * and inserts the corresponding data into the database table for each entity.
     *
     * Steps:
     * 1. Group operations by entity type.
     * 2. Prepare the data for each entity, including sync hash, owner ID, and timestamps.
     * 3. Insert the grouped data into the appropriate database tables.
     *
     * @param EntityInsert ...$operations An array of EntityInsert operations to be processed.
     * @throws \Exception If any insert operation fails.
     */
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
            $table = $this->getTableBuilder($tableName);

            if (!$table->insert($operations)) {
                throw new \Exception('Failed to insert entities');
            }
        }
    }

    /**
     * Updates multiple entity records in the database.
     *
     * This method processes an array of EntityUpdate operations, groups them by entity type,
     * and updates the corresponding data in the database table for each entity.
     *
     * Steps:
     * 1. Sort the operations by the actioned timestamp.
     * 2. Group entity IDs by entity type.
     * 3. Generate hashes for each entity based on the current data and the updates.
     * 4. Group the data to update by entity type.
     * 5. Update the entities in the database.
     *
     * @param EntityUpdate ...$operations An array of EntityUpdate operations to be processed.
     * @throws \Exception If any update operation fails.
     */
    function update(EntityUpdate ...$operations): void
    {
        $operations = $this->sortByActionedAt($operations);
        $groupsIdsByEntity = $this->groupIdsByEntity(...$operations);
        $groupHashesByEntity = [];
        $groupDataToUpdateByEntity = [];
        $columnId = EntitySynchronizable::ATTR_ID;


        // *************************************
        // 1. Generate hashes for each entity
        // *************************************

        foreach ($groupsIdsByEntity as $entity => $ids) {
            /**
             * @var EntitySynchronizable $entityClass
             */
            $entityClass = $this->entityMapper->getEloquentClassEntity($entity);
            $tableName = $entityClass::getTableName();
            $table = $this->getTableBuilder($tableName);

            $selectAttributes = array_map(fn(SyncParameter $parameter) => $parameter->name,
                array_filter($entityClass::parameters(),
                    fn(SyncParameter $parameter) => $parameter->type->isNotRelation())
            );
            $selectAttributes[] = $columnId;

            $dataEntities = $table->whereIn($columnId, $ids)->get($selectAttributes)->toArray();

            foreach ($dataEntities as $dataEntity) {
                $dataEntity = $this->convertSdtClassToArray($dataEntity);
                $entityId = $dataEntity[$columnId];
                /**
                 * Esta línea de código encuentra la última operación de actualización (EntityUpdate) correspondiente a una entidad específica (identificada por $entityId).
                 * Primero, invierte el array de operaciones ($operations) para que las operaciones más recientes estén primero.
                 * Luego, filtra las operaciones para mantener solo aquellas cuyo 'id' coincide con $entityId.
                 * Al usar array_reverse, nos aseguramos de que si hay múltiples actualizaciones para la misma entidad, la más reciente (última en el array original) será la primera en el array invertido.
                 * Al usar array_filter, eliminamos las operaciones que no coinciden con $entityId.
                 * Finalmente, array_merge([], ...) convierte el resultado filtrado en un array numerado (reindexado) y [0] selecciona el primer (y más reciente) elemento.
                 * Esto asegura que se aplique la última actualización relevante a la entidad.
                 * */
                $operationUpdate = array_merge([], array_filter(array_reverse($operations), fn(EntityUpdate $operation) => $operation->id == $entityId))[0];
                $dataEntity = array_merge($dataEntity, $operationUpdate->attributes);
                $groupHashesByEntity[$entity][$dataEntity[$columnId]] = Hasher::hash($dataEntity);
            }
        }

        // *************************************
        // 2. Group data to update by entity
        // *************************************

        foreach ($operations as $operation) {
            $data = $operation->attributes;
            $data[$columnId] = $operation->id;
            $data[EntitySynchronizable::ATTR_SYNC_HASH] = $groupHashesByEntity[$operation->entity][$operation->id];
            $data[EntitySynchronizable::ATTR_SYNC_UPDATED_AT] = $this->dateTimeUtil->getCurrent();
            $groupDataToUpdateByEntity[$operation->entity][] = $data;
        }


        // *************************************
        // 3. Update entities
        // *************************************

        foreach ($groupDataToUpdateByEntity as $entity => $data) {

            /**
             * @var EntitySynchronizable $entityClass
             */
            $entityClass = $this->entityMapper->getEloquentClassEntity($entity);
            $tableName = $entityClass::getTableName();

            foreach ($data as $item) {

                $table = $this->getTableBuilder($tableName);

                $id = $item[$columnId];
                unset($item[$columnId]);

                if ($this->isOperationIsFailure($table->where($columnId, $id)->update($item))) {
                    throw new \Exception(sprintf("[$tableName] Failed to update entity with id %s", $id));
                }
            }
        }

    }

    function delete(EntityUpdate ...$operations): void
    {
        // TODO: Implement delete() method.
    }


    /**
     * Group ids by entity name
     *
     * @param EntityOperation ...$operations
     * @return array
     */
    private function groupIdsByEntity(EntityOperation ...$operations): array
    {
        $groupOperationByEntity = [];

        foreach ($operations as $operation) {

            if (!isset($groupOperationByEntity[$operation->entity])) {
                $groupOperationByEntity[$operation->entity] = [];
            }

            if (in_array($operation->id, $groupOperationByEntity[$operation->entity])) {
                continue;
            }
            $groupOperationByEntity[$operation->entity][] = $operation->id;
        }

        return $groupOperationByEntity;
    }

    private function getTableBuilder(string $tableName): Builder
    {
        return (is_null($this->connectionName)) ? DB::table($tableName) :
            DB::connection($this->connectionName)->table($tableName);
    }

    private function convertSdtClassToArray($object): array
    {
        return json_decode(json_encode($object), true);
    }

    private function isOperationIsFailure(int $rowsAffected): bool
    {
        return $rowsAffected == 0;
    }

    /**
     * @param EntityOperation[] $operations
     * @return array
     */
    private function sortByActionedAt(array $operations): array
    {
        usort($operations, fn(EntityOperation $a, EntityOperation $b) => $a->actionedAt <=> $b->actionedAt);
        return $operations;
    }
}