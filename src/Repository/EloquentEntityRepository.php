<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Exception\OperationNotPermittedException;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Util\IDateTimeUtil;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\LookupSynchronizable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @internal Class EloquentEntityRepository
 *
 * EloquentEntityRepository is a repository implementation for managing entity operations
 * using Eloquent ORM. It handles the insertion, updating, deletion, and searching of entities
 * by interacting with the underlying database using Eloquent models.
 */
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
            $entityClass = $this->entityMapper->getEntityClass($entity);
            $tableName = $entityClass::getTableName();
            $table = $this->getTableBuilder($tableName);

            $this->validateOperation($entityClass);

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
            $entityClass = $this->entityMapper->getEntityClass($entity);
            $tableName = $entityClass::getTableName();
            $table = $this->getTableBuilder($tableName);

            $this->validateOperation($entityClass);

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
                $operationUpdate = array_merge([],
                    array_filter(
                        array_reverse($operations),
                        fn(EntityUpdate $operation) => $operation->id == $entityId)
                )[0];

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
            $entityClass = $this->entityMapper->getEntityClass($entity);
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

    /**
     * Deletes multiple entity records from the database and any related entities on cascade.
     *
     * @param EntityDelete ...$operations
     * @return void
     */
    function delete(EntityDelete ...$operations): void
    {

        $groupOperationByEntity = [];
        $dataPreparedToDelete = [];

        foreach ($operations as $operation) {
            $groupOperationByEntity[$operation->entity][] = $operation->id;
        }

        foreach ($groupOperationByEntity as $entity => $ids) {

            /**
             * @var EntitySynchronizable $entityClass
             */
            $entityClass = $this->entityMapper->getEntityClass($entity);

            $this->validateOperation($entityClass);

            foreach ($entityClass::query()->whereIn(EntitySynchronizable::ATTR_ID, $ids)->get() as $eloquentModel) {

                $entityData = $this->buildEntityData($eloquentModel);
                $idsRelated = $this->parseRelatedIds($entityData);

                // Delete related entities
                foreach ($idsRelated as $entityName => $ids) {
                    $dataPreparedToDelete[$entityName] =
                        array_merge($dataPreparedToDelete[$entityName] ?? [], (array)$ids);
                }
            }
        }

        // Delete entities and related entities
        foreach ($dataPreparedToDelete as $entityName => $ids) {
            /**
             * @var EntitySynchronizable $entityClass
             */
            $entityClass = $this->entityMapper->getEntityClass($entityName);
            $entityClass::query()->whereIn(EntitySynchronizable::ATTR_ID, $ids)->delete();
        }
    }

    /**
     * Searches all entities associated with a given user ID.
     *
     * This method retrieves all entity types mapped in the repository and searches for entities
     * associated with the provided user ID. It collects and merges results from all entities.
     *
     * @param int|string $userId The ID of the user whose entities are to be searched.
     * @return array An array of EntityData objects representing the entities associated with the user.
     */
    function searchAllEntitiesByUserId(int|string $userId): array
    {
        $entitiesMap = $this->entityMapper->getMap();
        $output = [];

        foreach ($entitiesMap as $entity) {
            $dataEntity = $this->searchEntities($userId, $entity->name);
            $output = array_merge($output, $dataEntity);
        }

        return $output;
    }

    /**
     * Searches all entities associated with a given user ID that have been updated after a specific timestamp.
     *
     * This method retrieves all entity types from the repository and searches for entities
     * associated with the provided user ID that have been updated after the specified timestamp.
     *
     * @param int|string $userId The ID of the user whose entities are to be searched.
     * @param int $timestamp The timestamp after which entities should be retrieved.
     * @return EntityData[] An array of EntityData objects representing the entities updated after the timestamp.
     */
    function searchEntitiesAfterUpdatedAt(string|int $userId, int $timestamp): array
    {
        $entities = $this->entityMapper->getEntities();
        $output = [];

        foreach ($entities as $entityName => $entityClass) {
            $dataEntity = $this->searchEntities($userId, $entityName, [], $timestamp);
            $output = array_merge($output, $dataEntity);
        }

        return $output;
    }

    /**
     * Searches for entities by user ID, entity name, and optional filters.
     *
     * This method searches for entities that match the specified user ID, entity name, optional IDs,
     * and an optional timestamp indicating the last update.
     *
     * @param int|string $userId The ID of the user whose entities are to be searched.
     * @param string $entityName The name of the entity to search for.
     * @param array $ids Optional array of entity IDs to filter by.
     * @param int|null $afterTimestamp Optional timestamp to filter entities updated after this time.
     * @return EntityData[] An array of EntityData objects representing the matched entities.
     */
    function searchEntities(string|int $userId,
                            string     $entityName,
                            array      $ids = [],
                            ?int       $afterTimestamp = null): array
    {
        /**
         * @var $entityClass EntitySynchronizable
         */
        $entityClass = $this->entityMapper->getEntityClass($entityName);
        $instanceClass = new $entityClass();

        /**
         * @var $queryBuilder \Illuminate\Database\Eloquent\Builder
         */
        $queryBuilder = $entityClass::query();

        if ($instanceClass instanceof EntitySynchronizable) {
            $queryBuilder = $queryBuilder->where(EntitySynchronizable::ATTR_SYNC_OWNER_ID, $userId);
        }

        if (count($ids) > 0) {
            $queryBuilder = $queryBuilder->whereIn(EntitySynchronizable::ATTR_ID, $ids);
        }

        if (!is_null($afterTimestamp) && $instanceClass instanceof EntitySynchronizable) {
            $queryBuilder = $queryBuilder->where(EntitySynchronizable::ATTR_SYNC_UPDATED_AT,
                ">",
                $this->dateTimeUtil->getFormatDate($this->dateTimeUtil->parseDatetime($afterTimestamp)->getTimestamp())
            );
        }

        $queryBuilder = $queryBuilder->get();

        return $this->iterateItemsAndSearchRelated($queryBuilder);
    }

    /**
     * Retrieves all entity hashes for a specific entity type associated with a given owner user ID.
     *
     * This method queries the database for hashes of entities of the specified type that belong to
     * the provided user ID, returning the results in descending order of entity ID.
     *
     * @param int|string $ownerUserId The ID of the owner whose entity hashes are to be retrieved.
     * @param string $entityName The name of the entity to retrieve hashes for.
     * @return array An array of arrays containing entity IDs and their corresponding hashes.
     */
    function getEntityHashes(string|int $ownerUserId, string $entityName): array
    {
        /**
         * @var $entityClass EntitySynchronizable
         */
        $entityClass = $this->entityMapper->getEntityClass($entityName);
        $result = $entityClass::query()->where(EntitySynchronizable::ATTR_SYNC_OWNER_ID, $ownerUserId)
            ->orderByDesc(EntitySynchronizable::ATTR_ID)
            ->get([EntitySynchronizable::ATTR_ID, EntitySynchronizable::ATTR_SYNC_HASH]);

        return $result->toArray();
    }

    /**
     * Checks if an entity exists for a given user ID, entity name, and entity ID.
     *
     * This method queries the database to determine if an entity with the specified ID and type
     * exists for the provided user ID.
     *
     * @param int|string $userId The ID of the user to check for the entity's existence.
     * @param string $entityName The name of the entity to check.
     * @param string $entityId The ID of the entity to check.
     * @return bool True if the entity exists, otherwise false.
     */
    function entityExists(int|string $userId, string $entityName, string $entityId): bool
    {
        /**
         * @var $entityClass EntitySynchronizable
         */
        $entityClass = $this->entityMapper->getEntityClass($entityName);
        return $entityClass::query()->where(EntitySynchronizable::ATTR_SYNC_OWNER_ID, $userId)
            ->where(EntitySynchronizable::ATTR_ID, $entityId)
            ->exists();
    }

    /**
     * Groups entity IDs by entity name from a list of operations.
     *
     * This method organizes entity IDs from the provided operations into groups based on the entity name.
     *
     * @param EntityOperation ...$operations The operations containing entity IDs to be grouped.
     * @return array An associative array where the keys are entity names and the values are arrays of entity IDs.
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

    /**
     * Retrieves a table builder instance for the specified table name.
     *
     * @param string $tableName The name of the table.
     * @return Builder The table builder instance.
     */
    private function getTableBuilder(string $tableName): Builder
    {
        return (is_null($this->connectionName)) ? DB::table($tableName) :
            DB::connection($this->connectionName)->table($tableName);
    }

    /**
     * Converts a standard data transfer object (SDT) class instance to a standard array.
     *
     * @param mixed $object The SDT class instance to convert.
     * @return array The converted array representation of the object.
     */
    private function convertSdtClassToArray($object): array
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * Checks if an operation failed based on the number of rows affected.
     *
     * @param int $rowsAffected The number of rows affected by the operation.
     * @return bool True if the operation failed (affected rows == 0), otherwise false.
     */
    private function isOperationIsFailure(int $rowsAffected): bool
    {
        return $rowsAffected == 0;
    }

    /**
     * Sorts a list of operations by the actioned timestamp.
     *
     * @param EntityOperation[] $operations The operations to sort.
     * @return EntityOperation[] The sorted list of operations.
     */
    private function sortByActionedAt(array $operations): array
    {
        usort($operations, fn(EntityOperation $a, EntityOperation $b) => $a->actionedAt <=> $b->actionedAt);
        return $operations;
    }

    /**
     * Builds an EntityData object from a parent entity, including its related entities.
     *
     * @param IEntitySynchronizable $parentEntity The parent entity to build the data from.
     * @return EntityData The built EntityData object containing the parent entity and its related entities.
     */
    private function buildEntityData(IEntitySynchronizable $parentEntity): EntityData
    {
        $entityData = new EntityData($parentEntity->getEntityName(), $this->prepareData($parentEntity->toArray()));

        if ($parentEntity instanceof LookupSynchronizable) {
            return $entityData;
        }

        $relationsOneOfMany = $parentEntity->getRelationsOneOfMany();

        foreach ($relationsOneOfMany as $relationMethod) {
            /**
             * @var $itemsRelated HasMany
             */
            $itemsRelated = $parentEntity->{$relationMethod}();

            $collectionItemsRelated = $this->iterateItemsAndSearchRelated($itemsRelated->get());

            $entityData->setEntitiesRelatedOneOfMany($relationMethod, $collectionItemsRelated);
        }

        $relationsOneOfOne = $parentEntity->getRelationsOneOfOne();

        foreach ($relationsOneOfOne as $relationMethod) {
            /**
             * @var $itemRelated HasOne
             */
            $itemRelated = $parentEntity->{$relationMethod}();

            if (!is_null($entityOne = $itemRelated->get()->first())) {
                $entityData->setEntitiesRelatedOneToOne($relationMethod, $this->buildEntityData($entityOne));
            }
        }

        return $entityData;
    }

    /**
     * Iterates over a collection of items and builds EntityData objects for each item,
     * including related entities.
     *
     * @param Collection $collectionItems The collection of items to iterate over.
     * @return EntityData[] An array of EntityData objects for each item in the collection.
     */
    private function iterateItemsAndSearchRelated(Collection $collectionItems): array
    {
        $output = [];

        foreach ($collectionItems as $item) {
            $output[] = $this->buildEntityData($item);
        }

        return $output;
    }

    /**
     * Prepares the data from a model by converting datetime fields to timestamps
     * and filtering out null attributes.
     *
     * @param array $modelData The model data to prepare.
     * @return array The prepared data with datetime fields converted to timestamps and null attributes removed.
     */
    private function prepareData(array $modelData): array
    {
        $output = $modelData;

        if (isset($modelData[EntitySynchronizable::ATTR_SYNC_CREATED_AT]))
            $output[EntitySynchronizable::ATTR_SYNC_CREATED_AT] = Carbon::create($this->dateTimeUtil->parseDatetime($modelData[EntitySynchronizable::ATTR_SYNC_CREATED_AT]))->timestamp;
        if (isset($modelData[EntitySynchronizable::ATTR_SYNC_UPDATED_AT]))
            $output[EntitySynchronizable::ATTR_SYNC_UPDATED_AT] = Carbon::create($this->dateTimeUtil->parseDatetime($modelData[EntitySynchronizable::ATTR_SYNC_UPDATED_AT]))->timestamp;

        // Filter attributes null
        return array_filter($output, fn($item) => !is_null($item));
    }

    /**
     * Extracts related entity IDs from an EntityData object.
     *
     * This method extracts IDs of related entities from the given EntityData object,
     * grouping them by entity name.
     *
     * @param EntityData $entityData The EntityData object to extract related IDs from.
     * @return string[] An associative array where the keys are related entity names and the values are arrays of IDs.
     */
    private function parseRelatedIds(EntityData $entityData): array
    {
        $idsOutput = [];
        $entityName = $entityData->name;
        $groupByEntity = function (array $data) {
            $output = [];
            foreach ($data as $index => $item) {
                foreach ($item as $entity => $ids) {
                    $output[$entity] = array_merge($output[$entity] ?? [], $ids);
                }
            }

            return $output;
        };

        foreach ($entityData->getData() as $key => $value) {
            // Validate if is a related entity
            if (str_starts_with($key, "_")) {
                $entitiesRelated = array_map(fn($item) => $this->parseRelatedIds($item), $value);
                $groups = $groupByEntity($entitiesRelated);
                foreach ($groups as $entity => $ids) {
                    $idsOutput[$entity] = array_merge($idsOutput[$entity] ?? [], $ids);
                }
                continue;
            }

            if ($key == EntitySynchronizable::ATTR_ID) {
                $idsOutput[$entityName][] = $value;
            }
        }

        return $idsOutput;
    }

    /**
     * Validates if an operation can be performed on a specified entity class.
     *
     * This method checks if the operation is permitted for the given entity class
     * and throws an exception if the operation is not allowed.
     *
     * @param string $entityClass The name of the entity class to validate.
     * @throws OperationNotPermittedException If the operation is not permitted for the given entity class.
     */
    private function validateOperation(string $entityClass): void
    {
        $instanceClass = new  $entityClass();

        if ($instanceClass instanceof EntitySynchronizable) {
            return;
        }

        throw new OperationNotPermittedException("Operation not permitted for entity $entityClass");
    }

}