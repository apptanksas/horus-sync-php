<?php

namespace AppTank\Horus\Core\Factory;

use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Model\EntityUpdateOrDelete;

/**
 * @internal Class EntityOperationFactory
 *
 * Factory class responsible for creating instances of entity operations:
 * inserts, updates, and deletes. It provides static methods to generate
 * `EntityInsert`, `EntityUpdate`, and `EntityDelete` objects with the necessary
 * parameters.
 *
 * @package AppTank\Horus\Core\Factory
 *
 * @author John Ospina
 * Year: 2024
 */
class EntityOperationFactory
{

    const array ATTRIBUTES_RESERVED = ["sync_owner_id", "sync_hash", "sync_created_at", "sync_updated_at", "sync_deleted_at"];

    /**
     * Creates an instance of `EntityInsert`.
     *
     * @param string|int $ownerId The ID of the owner of the entity.
     * @param string $entity The name of the entity.
     * @param array $data The data to be inserted.
     * @param \DateTimeImmutable $actionedAt The timestamp of the action.
     *
     * @return EntityInsert The created `EntityInsert` object.
     */
    public static function createEntityInsert(
        string|int         $ownerId,
        string             $entity,
        array              $data,
        \DateTimeImmutable $actionedAt
    ): EntityInsert
    {
        //Filter attributes reserved
        $data = array_filter($data, function ($key) {return !in_array($key, self::ATTRIBUTES_RESERVED);}, ARRAY_FILTER_USE_KEY);
        return new EntityInsert($ownerId, $entity, $actionedAt, $data);
    }

    /**
     * Creates an instance of `EntityUpdate`.
     *
     * @param string|int $ownerId The ID of the owner of the entity.
     * @param string $entity The name of the entity.
     * @param string $id The ID of the entity to be updated.
     * @param array $attributes The attributes to be updated.
     * @param \DateTimeImmutable $actionedAt The timestamp of the action.
     *
     * @return EntityUpdate The created `EntityUpdate` object.
     */
    public static function createEntityUpdate(
        string|int         $ownerId,
        string             $entity,
        string             $id,
        array              $attributes,
        \DateTimeImmutable $actionedAt
    ): EntityUpdate
    {
        //Filter attributes reserved
        $attributes = array_filter($attributes, function ($key) {return !in_array($key, self::ATTRIBUTES_RESERVED);}, ARRAY_FILTER_USE_KEY);
        return new EntityUpdate($ownerId, $entity, $id, $actionedAt, $attributes);
    }

    /**
     * Creates an instance of `EntityDelete`.
     *
     * @param string|int $ownerId The ID of the owner of the entity.
     * @param string $entity The name of the entity.
     * @param string $id The ID of the entity to be deleted.
     * @param \DateTimeImmutable $actionedAt The timestamp of the action.
     *
     * @return EntityDelete The created `EntityDelete` object.
     */
    public static function createEntityDelete(
        string|int         $ownerId,
        string             $entity,
        string             $id,
        \DateTimeImmutable $actionedAt
    ): EntityDelete
    {
        return new EntityDelete($ownerId, $entity, $id, $actionedAt);
    }
}
