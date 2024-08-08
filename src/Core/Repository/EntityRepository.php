<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityUpdate;

interface EntityRepository
{
    function insert(EntityInsert ...$operations): void;

    function update(EntityUpdate ...$operations): void;

    function delete(EntityUpdate ...$operations): void;


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
    function searchEntity(string|int $userId,
                          string     $entityName,
                          array      $ids = [],
                          ?int       $timestampAfter = null): array;
}