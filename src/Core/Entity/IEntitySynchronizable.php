<?php

namespace AppTank\Horus\Core\Entity;

/**
 * @internal Interface IEntitySynchronizable
 *
 * Defines the contract for entities that can be synchronized. Entities implementing this
 * interface must provide methods to retrieve synchronization parameters, entity names,
 * version numbers, and schema information.
 *
 * @package AppTank\Horus\Core\Entity
 *
 * @author John Ospina
 * Year: 2024
 */
interface IEntitySynchronizable
{
    /**
     * Get the synchronization parameters for the entity.
     *
     * @return SyncParameter[] An array of synchronization parameters.
     */
    public static function parameters(): array;

    /**
     * Get the name of the entity.
     *
     * @return string The entity name.
     */
    public static function getEntityName(): string;

    /**
     * Get the version number of the entity.
     *
     * @return int The version number.
     */
    public static function getVersionNumber(): int;

    /**
     * Get the base synchronization parameters for the entity.
     *
     * @param int $baseVersion The base version number.
     * @return array An array of base synchronization parameters.
     */
    public static function baseParameters(int $baseVersion): array;

    /**
     * Get the name of the table associated with the entity.
     *
     * @return string The table name.
     */
    public static function getTableName(): string;

    /**
     * Get the schema definition of the entity.
     *
     * @return array An array representing the schema.
     */
    public static function schema(): array;

    /**
     * Get the indexes of the columns in the entity.
     *
     * @return array An array of column indexes.
     */
    public static function getColumnIndexes(): array;
}
