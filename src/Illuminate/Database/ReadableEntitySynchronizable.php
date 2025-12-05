<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Horus;

/**
 * Class LookupSynchronizable
 *
 * This abstract class extends `BaseSynchronizable` and implements `IEntitySynchronizable`.
 * It is designed for entities that are used for lookup purposes and provides default
 * implementations for managing synchronization of lookup entities. The `baseParameters`
 * method is customized to use integer primary keys and additional synchronization attributes.
 *
 * @package AppTank\Horus\Illuminate\Database
 */
abstract class ReadableEntitySynchronizable extends EntitySynchronizable implements IEntitySynchronizable
{
    public $timestamps = false;
    public $incrementing = true;
    // The default primary key type is integer.
    protected static bool $usesUuid = false;

    /**
     * Get the base synchronization parameters for lookup entities.
     *
     * @param int $baseVersion The base version for synchronization.
     * @return SyncParameter[] List of base synchronization parameters.
     */
    public static function baseParameters(int $baseVersion): array
    {
        return [
            (static::$usesUuid) ? SyncParameter::createPrimaryKeyUUID(self::ATTR_ID, $baseVersion) : SyncParameter::createPrimaryKeyInteger(self::ATTR_ID, $baseVersion),
            SyncParameter::createTimestamp(self::ATTR_SYNC_DELETED_AT, $baseVersion)
        ];
    }

    /**
     * Get the table name for lookup entities.
     *
     * @return string Table name for lookup entities.
     */
    final public static function getTableName(): string
    {
        return Horus::getInstance()->getConfig()->prefixTables . "_" . static::getEntityName();
    }

    // ------------------------------------------------------------------------
    // RELATIONS
    // ------------------------------------------------------------------------

    /**
     * Get one-to-many relation methods.
     *
     * @return string[] List of one-to-many relation methods.
     */
    public function getRelationsOneOfMany(): array
    {
        return [];
    }

    /**
     * Get one-to-one relation methods.
     *
     * @return string[] List of one-to-one relation methods.
     */
    public function getRelationsOneOfOne(): array
    {
        return [];
    }

}
