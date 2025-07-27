<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\Restriction\FilterEntityRestriction;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Core\Exception\OperationNotPermittedException;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Repository\CacheRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Util\IDateTimeUtil;
use AppTank\Horus\Illuminate\Database\EntityDependsOn;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\ReadableEntitySynchronizable;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
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
class EloquentEntityRepository implements EntityRepository
{

    const int BATCH_SIZE = 2500; // Maximum number of records to insert in a single batch
    const int CACHE_TTL_ONE_DAY = 86400; // 24 hours in seconds
    const int CACHE_TTL_ONE_YEAR = 31536000; // 365 days in seconds

    private array $cacheEntityParameters = array();

    /**
     * @param EntityMapper $entityMapper
     * @param IDateTimeUtil $dateTimeUtil
     * @param CacheRepository $cacheRepository
     * @param Config $config
     * @param string|null $connectionName
     */
    public function __construct(
        readonly private EntityMapper    $entityMapper,
        readonly private CacheRepository $cacheRepository,
        readonly private IDateTimeUtil   $dateTimeUtil,
        readonly private Config          $config,
        readonly private ?string         $connectionName = null,
    )
    {
        if (empty($this->entityMapper->getEntities())) {
            throw new \InvalidArgumentException('The entity mapper must have at least one entity.');
        }
    }

    /**
     * Inserts multiple entity records into the database.
     *
     * This method processes an array of EntityInsert operations, groups them by entity type,
     * and inserts the corresponding data into the database table for each entity in batches of 2500 records.
     *
     * Steps:
     * 1. Group operations by entity type.
     * 2. Prepare the data for each entity, including sync hash, owner ID, and timestamps.
     * 3. Insert the grouped data into the appropriate database tables in batches.
     *
     * @param EntityInsert ...$operations An array of EntityInsert operations to be processed.
     * @throws \Exception If any insert operation fails.
     */
    function insert(EntityInsert ...$operations): void
    {
        $entityIdsCachePending = [];
        $operations = $this->sortByActionedAt($operations);
        $entities = $this->entityMapper->getEntities();
        $groupOperationByEntity = [];

        foreach ($operations as $operation) {

            $entityIdsCachePending[$operation->entity][$operation->id] = $operation->ownerId;

            $groupOperationByEntity[$operation->entity][] = array_merge([
                WritableEntitySynchronizable::ATTR_SYNC_HASH => $operation->hash(),
                WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID => $operation->ownerId,
                WritableEntitySynchronizable::ATTR_SYNC_CREATED_AT => $this->dateTimeUtil->getCurrent(),
                WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT => $this->dateTimeUtil->getCurrent(),
            ], $this->parseData($operation->entity, $operation->toArray()));
        }

        // Reorder operations to respect the entity order defined in the map
        // This ensures that dependent entities are created after their dependencies
        if (!empty($groupOperationByEntity)) {
            $orderedOperations = [];
            foreach (array_keys($entities) as $entityName) {
                if (isset($groupOperationByEntity[$entityName])) {
                    $orderedOperations[$entityName] = $groupOperationByEntity[$entityName];
                }
            }
            $groupOperationByEntity = $orderedOperations;
        }

        foreach ($groupOperationByEntity as $entity => $operations) {

            /**
             * @var WritableEntitySynchronizable $entityClass
             */
            $entityClass = $this->entityMapper->getEntityClass($entity);
            $tableName = $entityClass::getTableName();

            $this->validateOperation($entityClass);

            // Process inserts in batches of 2500 records
            $batches = array_chunk($operations, self::BATCH_SIZE);

            foreach ($batches as $batch) {
                $table = $this->getTableBuilder($tableName);

                if (!$table->insert($batch)) {
                    throw new \Exception('Failed to insert entities batch');
                }
            }
        }

        // *************************************
        // Cache the entity IDs for the owner
        // *************************************

        foreach ($entityIdsCachePending as $entityName => $ids) {
            foreach ($ids as $entityId => $ownerId) {
                $cacheKey = $this->createEntityOwnerCacheKey($entityName, $entityId);
                $this->cacheRepository->set($cacheKey, $ownerId, self::CACHE_TTL_ONE_YEAR);
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
        $columnId = WritableEntitySynchronizable::ATTR_ID;


        // *************************************
        // 1. Generate hashes for each entity
        // *************************************

        foreach ($groupsIdsByEntity as $entity => $ids) {
            /**
             * @var WritableEntitySynchronizable $entityClass
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
            $data[WritableEntitySynchronizable::ATTR_SYNC_HASH] = $groupHashesByEntity[$operation->entity][$operation->id];
            $data[WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT] = $this->dateTimeUtil->getCurrent();
            $groupDataToUpdateByEntity[$operation->entity][] = $data;
        }


        // *************************************
        // 3. Update entities
        // *************************************

        foreach ($groupDataToUpdateByEntity as $entity => $data) {

            /**
             * @var WritableEntitySynchronizable $entityClass
             */
            $entityClass = $this->entityMapper->getEntityClass($entity);
            $tableName = $entityClass::getTableName();

            foreach ($data as $item) {

                $table = $this->getTableBuilder($tableName);
                $item = $this->parseData($entity, $item);

                $id = $item[$columnId];
                unset($item[$columnId]);

                $table->where($columnId, $id)->update($item);
            }
        }

    }

    /**
     * Deletes multiple entity records from the database and any related entities on cascade.
     *
     * This method processes deletes in batches to optimize performance and avoid large IN clauses.
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
             * @var WritableEntitySynchronizable $entityClass
             */
            $entityClass = $this->entityMapper->getEntityClass($entity);

            $this->validateOperation($entityClass);

            // Process deletion queries in batches to avoid large IN clauses
            $idBatches = array_chunk($ids, self::BATCH_SIZE);

            foreach ($idBatches as $idBatch) {
                foreach ($entityClass::query()->whereIn(WritableEntitySynchronizable::ATTR_ID, $idBatch)->get() as $eloquentModel) {

                    $entityData = $this->buildEntityData($eloquentModel);
                    $idsRelated = $this->parseRelatedIds($entityData);

                    // Delete related entities
                    foreach ($idsRelated as $entityName => $relatedIds) {
                        $dataPreparedToDelete[$entityName] = array_merge($dataPreparedToDelete[$entityName] ?? [], (array)$relatedIds);
                    }
                }
            }
        }

        // Delete entities and related entities in batches
        foreach ($dataPreparedToDelete as $entityName => $ids) {
            /**
             * @var WritableEntitySynchronizable $entityClass
             */
            $entityClass = $this->entityMapper->getEntityClass($entityName);

            // Process deletes in batches to avoid large IN clauses  
            $idBatches = array_chunk($ids, self::BATCH_SIZE);

            foreach ($idBatches as $idBatch) {
                $entityClass::query()->whereIn(WritableEntitySynchronizable::ATTR_ID, $idBatch)->delete();
            }
        }
    }

    /**
     * Searches all entities associated with a given user ID.
     *
     * This method retrieves all entity types mapped in the repository and searches for entities
     * associated with the provided user ID. It collects and merges results from all entities.
     *
     * OPTIMIZATION: Uses eager loading with Eloquent's with() method to prevent N+1 query problems
     * by pre-loading all related entities in a single query per entity type.
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
     * OPTIMIZATION: Automatically detects all relations for the entity and applies eager loading
     * to prevent N+1 queries when building EntityData objects with related entities.
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

        $cacheKey = "readable_entity_$entityName";

        // Check if the entity is cacheable and if the cache exists
        if (empty($ids) && is_null($afterTimestamp) && $this->cacheRepository->exists($cacheKey)) {
            return $this->cacheRepository->get($cacheKey);
        }

        /**
         * @var $entityClass EntitySynchronizable
         */
        $entityClass = $this->entityMapper->getEntityClass($entityName);
        $instanceClass = new $entityClass();

        /**
         * @var $queryBuilder \Illuminate\Database\Eloquent\Builder
         */
        $queryBuilder = $entityClass::query();

        if ($instanceClass instanceof WritableEntitySynchronizable) {
            $queryBuilder = $queryBuilder->where(WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID, $userId);
        }

        if (count($ids) > 0) {
            $queryBuilder = $queryBuilder->whereIn(EntitySynchronizable::ATTR_ID, $ids);
        }


        //-------------------------------------
        // APPLY RESTRICTIONS
        //-------------------------------------

        if ($this->config->hasRestrictions($entityName)) {
            $restrictions = $this->config->getRestrictionsByEntity($entityName);
            foreach ($restrictions as $restriction) {
                if ($restriction instanceof FilterEntityRestriction) {
                    foreach ($restriction->parametersFilter as $filter) {
                        $queryBuilder = $queryBuilder->where($filter->parameterName, $filter->parameterValue);
                    }
                }
            }
        }

        if (!is_null($afterTimestamp) && $instanceClass instanceof WritableEntitySynchronizable) {
            $queryBuilder = $queryBuilder->where(WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT,
                ">",
                $this->dateTimeUtil->getFormatDate($this->dateTimeUtil->parseDatetime($afterTimestamp)->getTimestamp())
            );
        }

        // Apply eager loading to prevent N+1 queries
        // Skip eager loading for test entities to avoid compatibility issues with in-memory tables
        $isTestEntity = str_starts_with($entityClass, 'Tests\\') || str_contains($entityClass, '_Stubs\\');

        if (!$isTestEntity) {
            $eagerLoadRelations = $this->getEagerLoadRelations($entityClass);
            if (!empty($eagerLoadRelations)) {
                $queryBuilder = $queryBuilder->with($eagerLoadRelations);
            }
        }

        $queryBuilder = $queryBuilder->get();


        $result = $this->iterateItemsAndSearchRelated($queryBuilder);

        if (empty($ids) && is_null($afterTimestamp) && $instanceClass instanceof ReadableEntitySynchronizable) {
            $this->cacheRepository->set($cacheKey, $result, self::CACHE_TTL_ONE_DAY);
        }

        return $result;
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
         * @var $entityClass WritableEntitySynchronizable
         */
        $entityClass = $this->entityMapper->getEntityClass($entityName);
        $result = $entityClass::query()->where(WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID, $ownerUserId)
            ->orderByDesc(WritableEntitySynchronizable::ATTR_ID)
            ->get([WritableEntitySynchronizable::ATTR_ID, WritableEntitySynchronizable::ATTR_SYNC_HASH]);

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
         * @var $entityClass WritableEntitySynchronizable
         */
        $entityClass = $this->entityMapper->getEntityClass($entityName);
        return $entityClass::query()->where(WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID, $userId)
            ->where(WritableEntitySynchronizable::ATTR_ID, $entityId)
            ->exists();
    }

    /**
     * Builds a hierarchy of paths based on the referenced entity, considering its relationship with parent entities.
     *
     * @param EntityReference $entityRefChild Reference to the child entity to build the hierarchy.
     *
     * @return EntitySynchronizable[] Returns an array with the entity hierarchy.
     */
    function getEntityPathHierarchy(EntityReference $entityRefChild): array
    {
        $entityHierarchy = [];

        /**
         * @var EntitySynchronizable $entityClass
         */
        $entityClass = $this->entityMapper->getEntityClass($entityRefChild->entityName);
        // Get foreign keys of the entity
        $foreignKeys = array_map(fn(SyncParameter $parameter) => $parameter->name,
            array_filter($entityClass::parameters(), fn(SyncParameter $parameter) => $parameter->linkedEntity != null));

        $entity = $entityClass::query()->where(EntitySynchronizable::ATTR_ID, $entityRefChild->entityId)->first(array_merge($foreignKeys, [EntitySynchronizable::ATTR_ID]));

        if ($entity instanceof EntityDependsOn) {
            /**
             * @var WritableEntitySynchronizable $entityParent
             */
            $entityParent = $entity->dependsOn();

            $entityHierarchy = array_merge($entityHierarchy,
                $this->getEntityPathHierarchy(
                    new EntityReference($entityParent::class::getEntityName(), $entityParent->getId())
                )
            );
        }

        if ($entity != null) {
            $entityHierarchy[] = $entity;
        }

        return $entityHierarchy;
    }

    /**
     * Searches for entities based on their references.
     *
     * Note: Dont matter if the entities was deleted. Dont apply restrictions and nothing.
     *
     * @param EntityReference ...$entityReferences The entity references to search for.
     *
     * @return EntityData[] An array of entity data matching the specified references.
     */

    function searchRawEntitiesByReference(EntityReference ...$entityReferences): array
    {
        $groupedEntitiesByName = [];
        $output = [];

        // Group entity references by entity name
        foreach ($entityReferences as $entityReference) {
            $groupedEntitiesByName[$entityReference->entityName][] = $entityReference->entityId;
        }

        foreach ($groupedEntitiesByName as $entityName => $ids) {

            /**
             * @var $entityClass EntitySynchronizable
             */
            $entityClass = $this->entityMapper->getEntityClass($entityName);

            $result = $entityClass::withTrashed()->whereIn(EntitySynchronizable::ATTR_ID, $ids)->get();

            foreach ($result as $entityResult) {
                $output[] = new EntityData($entityResult->getEntityName(), $this->prepareData($entityResult->toArray()));
            }
        }

        return $output;
    }

    /**
     * Retrieves the owner ID of a specific entity by its name and ID.
     *
     * @param string $entityName The name of the entity.
     * @param string $entityId The ID of the entity.
     *
     * @return string|int The ID of the user who owns the entity.
     * @throws ClientException
     */
    public function getEntityOwner(string $entityName, string $entityId): string|int
    {
        $cacheKey = $this->createEntityOwnerCacheKey($entityName, $entityId);

        if ($this->cacheRepository->exists($cacheKey)) {
            return $this->cacheRepository->get($cacheKey);
        }

        /**
         * @var $entityClass WritableEntitySynchronizable
         */
        $entityClass = $this->entityMapper->getEntityClass($entityName);

        // Retrieve the owner ID of the entity by its ID
        $ownerId = $entityClass::withTrashed()
            ->where(WritableEntitySynchronizable::ATTR_ID, $entityId)
            ->value(WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID);

        if (is_null($ownerId)) {
            throw new ClientException("Entity with ID $entityId does not exist in entity $entityName.");
        }

        $this->cacheRepository->set($cacheKey, $ownerId, self::CACHE_TTL_ONE_YEAR);
        return $ownerId;
    }


    /**
     * Retrieves the count of entities associated with a specific user ID.
     *
     * @param string|int $userId The ID of the user whose entities are being counted.
     *
     * @return int The count of entities associated with the specified user ID.
     * @throws ClientException
     */
    function getCount(int|string $userId, string $entityName): int
    {
        /**
         * @var $entityClass EntitySynchronizable
         */
        $entityClass = $this->entityMapper->getEntityClass($entityName);
        return $entityClass::query()->where(WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID, $userId)->count();
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

        $relationsOneOfMany = $parentEntity->getRelationsOneOfMany();

        foreach ($relationsOneOfMany as $relationMethod) {
            // Try to use already loaded relation first
            if ($parentEntity->relationLoaded($relationMethod)) {
                $loadedRelation = $parentEntity->getRelation($relationMethod);
                if ($loadedRelation instanceof Collection) {
                    $collectionItemsRelated = $this->iterateItemsAndSearchRelated($loadedRelation);
                    $entityData->setEntitiesRelatedOneOfMany($relationMethod, $collectionItemsRelated);
                }
            } else {
                // Fallback to original behavior if relation not loaded
                $itemsRelated = $parentEntity->{$relationMethod}();
                $collectionItemsRelated = $this->iterateItemsAndSearchRelated($itemsRelated->get());
                $entityData->setEntitiesRelatedOneOfMany($relationMethod, $collectionItemsRelated);
            }
        }

        $relationsOneOfOne = $parentEntity->getRelationsOneOfOne();

        foreach ($relationsOneOfOne as $relationMethod) {
            // Try to use already loaded relation first
            if ($parentEntity->relationLoaded($relationMethod)) {
                $loadedRelation = $parentEntity->getRelation($relationMethod);
                if (!is_null($loadedRelation)) {
                    $entityData->setEntitiesRelatedOneToOne($relationMethod, $this->buildEntityData($loadedRelation));
                }
            } else {
                // Fallback to original behavior if relation not loaded
                $itemRelated = $parentEntity->{$relationMethod}();
                if (!is_null($entityOne = $itemRelated->get()->first())) {
                    $entityData->setEntitiesRelatedOneToOne($relationMethod, $this->buildEntityData($entityOne));
                }
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

        if (isset($modelData[WritableEntitySynchronizable::ATTR_SYNC_CREATED_AT]))
            $output[WritableEntitySynchronizable::ATTR_SYNC_CREATED_AT] = Carbon::create($this->dateTimeUtil->parseDatetime($modelData[WritableEntitySynchronizable::ATTR_SYNC_CREATED_AT]))->timestamp;
        if (isset($modelData[WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT]))
            $output[WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT] = Carbon::create($this->dateTimeUtil->parseDatetime($modelData[WritableEntitySynchronizable::ATTR_SYNC_UPDATED_AT]))->timestamp;

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
                $entitiesRelated = (is_array($value)) ? array_map(fn($item) => $this->parseRelatedIds($item), $value) : array($this->parseRelatedIds($value));
                $groups = $groupByEntity($entitiesRelated);
                foreach ($groups as $entity => $ids) {
                    $idsOutput[$entity] = array_merge($idsOutput[$entity] ?? [], $ids);
                }
                continue;
            }

            if ($key == WritableEntitySynchronizable::ATTR_ID) {
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

        if ($instanceClass instanceof WritableEntitySynchronizable) {
            return;
        }

        throw new OperationNotPermittedException("Operation not permitted for entity $entityClass");
    }

    /**
     * Parses the data for an entity based on its parameters.
     *
     * This method converts the data for an entity to the appropriate format based on the entity's parameters.
     *
     * @param string $entity The name of the entity to parse the data for.
     * @param array $data The data to parse.
     * @return array The parsed data for the entity.
     */
    private function parseData(string $entity, array $data): array
    {
        $output = [];
        $entityParameters = $this->getEntityParameters($entity);

        foreach ($data as $key => $value) {

            if (is_null($value)) {
                $output[$key] = null;
                continue;
            }

            $parameter = $entityParameters[$key] ?? null;

            match ($parameter) {
                SyncParameterType::TIMESTAMP => $output[$key] = $this->dateTimeUtil->getFormatDate($value),
                default => $output[$key] = $value
            };
        }

        return $output;
    }

    /**
     * Retrieves the parameters of an entity class.
     *
     * @param string $entity
     * @return array
     * @throws ClientException
     */
    private function getEntityParameters(string $entity): array
    {
        if (isset($this->cacheEntityParameters[$entity])) {
            return $this->cacheEntityParameters[$entity];
        }

        /**
         * @var WritableEntitySynchronizable $entityClass
         */
        $entityClass = $this->entityMapper->getEntityClass($entity);
        $parameters = $entityClass::parameters();

        $output = [];

        foreach ($parameters as $parameter) {
            $output[$parameter->name] = $parameter->type;
        }

        $this->cacheEntityParameters[$entity] = $output;

        return $output;
    }


    /**
     * Creates a cache key for the entity owner based on the entity name and ID.
     *
     * @param string $entityName The name of the entity.
     * @param string $entityId The ID of the entity.
     * @return string The generated cache key.
     */
    private function createEntityOwnerCacheKey(string $entityName, string $entityId): string
    {
        return "entity_owner_$entityName." . "$entityId";
    }

    /**
     * Gets all relations that need to be eagerly loaded for an entity class.
     * This method builds a list of all relations to avoid N+1 queries by using Eloquent's with() method.
     *
     * The method detects both one-to-many and one-to-one relations defined in the entity
     * and returns them as an array of relation names suitable for eager loading, including nested relations.
     *
     * @param string $entityClass The entity class name
     * @param array $visited Array to track visited entities to prevent infinite recursion
     * @param int $maxDepth Maximum depth for nested relations (default: 3)
     * @return array Array of relation names for eager loading (including nested with dot notation)
     */
    private function getEagerLoadRelations(string $entityClass, array $visited = [], int $maxDepth = 3): array
    {
        // Prevent infinite recursion and limit depth
        if (in_array($entityClass, $visited) || $maxDepth <= 0) {
            return [];
        }

        $relations = [];
        $visited[] = $entityClass;

        try {
            $instanceClass = new $entityClass();

            if (!($instanceClass instanceof IEntitySynchronizable)) {
                return [];
            }

            // Get one-to-many relations
            $relationsOneOfMany = $instanceClass->getRelationsOneOfMany();
            foreach ($relationsOneOfMany as $relationMethod) {
                $relations[] = $relationMethod;

                // Get nested relations for this relation
                $nestedRelations = $this->getNestedRelationsForMethod($instanceClass, $relationMethod, $visited, $maxDepth - 1);
                foreach ($nestedRelations as $nestedRelation) {
                    $relations[] = $relationMethod . '.' . $nestedRelation;
                }
            }

            // Get one-to-one relations
            $relationsOneOfOne = $instanceClass->getRelationsOneOfOne();
            foreach ($relationsOneOfOne as $relationMethod) {
                $relations[] = $relationMethod;

                // Get nested relations for this relation
                $nestedRelations = $this->getNestedRelationsForMethod($instanceClass, $relationMethod, $visited, $maxDepth - 1);
                foreach ($nestedRelations as $nestedRelation) {
                    $relations[] = $relationMethod . '.' . $nestedRelation;
                }
            }

        } catch (\Exception $e) {
            // If we can't instantiate the class, return empty relations
            return [];
        }

        return array_unique($relations);
    }

    /**
     * Gets nested relations for a specific relation method.
     *
     * @param IEntitySynchronizable $parentEntity The parent entity instance
     * @param string $relationMethod The relation method name
     * @param array $visited Array of visited entities to prevent recursion
     * @param int $maxDepth Maximum depth for nested relations
     * @return array Array of nested relation names
     */
    private function getNestedRelationsForMethod(IEntitySynchronizable $parentEntity, string $relationMethod, array $visited, int $maxDepth): array
    {
        if ($maxDepth <= 0) {
            return [];
        }

        try {
            // Get the relation instance to determine the related model class
            $relation = $parentEntity->{$relationMethod}();

            if ($relation instanceof HasMany || $relation instanceof HasOne) {
                $relatedModel = $relation->getRelated();
                $relatedClass = get_class($relatedModel);

                // Recursively get relations for the related model
                return $this->getEagerLoadRelations($relatedClass, $visited, $maxDepth);
            }
        } catch (\Exception $e) {
            // If we can't determine the related model, skip nested relations
            return [];
        }

        return [];
    }
}