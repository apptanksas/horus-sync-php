<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityUpdate;

interface EntityRepository
{
    /**
     * Inserts multiple entity records into the database.
     *
     * @param EntityInsert ...$operations
     * @return void
     */
    function insert(EntityInsert ...$operations): void;

    /**
     * Updates multiple entity records in the database.
     *
     * @param EntityUpdate ...$operations
     * @return void
     */
    function update(EntityUpdate ...$operations): void;

    /**
     * Deletes multiple entity records from the database and any related entities on cascade.
     *
     * @param EntityDelete ...$operations
     * @return void
     */
    function delete(EntityDelete ...$operations): void;


    /**
     * Search all entities by user id
     *
     * @param string|int $userId
     * @return EntityData[]
     */
    function searchAllEntitiesByUserId(string|int $userId): array;

    /**
     * Search entities that have been updated after the given timestamp
     *
     * @param string|int $userId
     * @param int $timestamp
     * @return EntityData[]
     */
    function searchEntitiesAfterUpdatedAt(string|int $userId, int $timestamp): array;

    /**
     * @param string|int $userId
     * @param string $entityName
     * @param array $ids
     * @param int|null $timestampAfter
     * @return EntityData[]
     */
    function searchEntities(string|int $userId,
                            string     $entityName,
                            array      $ids = [],
                            ?int       $timestampAfter = null): array;
}