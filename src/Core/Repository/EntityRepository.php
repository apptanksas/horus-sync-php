<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

/**
 * Interface EntityRepository
 *
 * Defines the contract for managing entity operations in a repository. This includes inserting, updating,
 * and deleting entities, as well as searching for entities based on various criteria. Implementations of this
 * interface should handle the persistence and retrieval of entity data from a storage system.
 *
 * @package AppTank\Horus\Core\Repository
 *
 * @author John Ospina
 * Year: 2024
 */
interface EntityRepository
{
    /**
     * Inserts multiple entity records into the database.
     *
     * @param EntityInsert ...$operations The entity insert operations to be performed.
     *
     * @return void
     */
    function insert(EntityInsert ...$operations): void;

    /**
     * Updates multiple entity records in the database.
     *
     * @param EntityUpdate ...$operations The entity update operations to be performed.
     *
     * @return void
     */
    function update(EntityUpdate ...$operations): void;

    /**
     * Deletes multiple entity records from the database and any related entities on cascade.
     *
     * @param EntityDelete ...$operations The entity delete operations to be performed.
     *
     * @return void
     */
    function delete(EntityDelete ...$operations): void;

    /**
     * Searches all entities associated with a specific user ID.
     *
     * @param string|int $userId The ID of the user whose entities are being searched.
     *
     * @return EntityData[] An array of entity data associated with the specified user ID.
     */
    function searchAllEntitiesByUserId(string|int $userId): array;

    /**
     * Searches entities that have been updated after the given timestamp.
     *
     * @param string|int $userId The ID of the user whose entities are being searched.
     * @param int $timestamp The timestamp after which entities have been updated.
     *
     * @return EntityData[] An array of entity data that have been updated after the specified timestamp.
     */
    function searchEntitiesAfterUpdatedAt(string|int $userId, int $timestamp): array;

    /**
     * Searches entities based on user ID, entity name, and optionally entity IDs and a timestamp.
     *
     * @param string|int $userId The ID of the user whose entities are being searched.
     * @param string $entityName The name of the entity to search for.
     * @param array $ids Optional. The IDs of the entities to search for.
     * @param int|null $afterTimestamp Optional. The timestamp after which entities should be returned.
     *
     * @return EntityData[] An array of entity data matching the search criteria.
     */
    function searchEntities(string|int $userId,
                            string     $entityName,
                            array      $ids = [],
                            ?int       $afterTimestamp = null): array;

    /**
     * Retrieves all entity hashes by entity name.
     *
     * @param string|int $ownerUserId The ID of the owner user.
     * @param string $entityName The name of the entity for which hashes are being retrieved.
     *
     * @return array An array of entity hashes associated with the specified entity name.
     */
    function getEntityHashes(string|int $ownerUserId, string $entityName): array;

    /**
     * Checks if a specific entity exists.
     *
     * @param string|int $userId The ID of the user who owns the entity.
     * @param string $entityName The name of the entity.
     * @param string $entityId The ID of the entity.
     *
     * @return bool True if the entity exists; otherwise, false.
     */
    function entityExists(string|int $userId, string $entityName, string $entityId): bool;


    /**
     * Builds a hierarchy of paths based on the referenced entity, considering its relationship with parent entities.
     *
     * @param EntityReference $entityRefChild Reference to the child entity to build the hierarchy.
     *
     * @return EntitySynchronizable[] Returns an array with the entity hierarchy.
     */
    function getEntityPathHierarchy(EntityReference $entityRefChild): array;

    /**
     * Retrieves the count of entities associated with a specific user ID.
     *
     * @param string|int $userId The ID of the user whose entities are being counted.
     *
     * @return int The count of entities associated with the specified user ID.
     * @throws ClientException
     */
    function getCount(string|int $userId, string $entityName): int;

    /**
     * Searches for entities based on their references.
     *
     * Note: Dont matter if the entities was deleted. Dont apply restrictions and nothing.
     *
     * @param EntityReference ...$entityReferences The entity references to search for.
     *
     * @return EntityData[] An array of entity data matching the specified references.
     */
    function searchRawEntitiesByReference(EntityReference ...$entityReferences): array;


    /**
     * Retrieves the owner ID of a specific entity by its name and ID.
     *
     * @param string $entityName The name of the entity.
     * @param string $entityId The ID of the entity.
     *
     * @return string|int The ID of the user who owns the entity.
     */
    function getEntityOwner(string $entityName, string $entityId): string|int;

    /**
     * Retrieves the parent owner of a child entity based on its reference and data.
     *
     * @param EntityReference $childReference The reference of the child entity.
     * @param array $childData The data of the child entity.
     * @return string|int|null
     */
    function getEntityParentOwner(EntityReference $childReference, array $childData): string|int|null;
}
