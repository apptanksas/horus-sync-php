<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;

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
abstract class LookupSynchronizable extends BaseSynchronizable implements IEntitySynchronizable
{
    public $timestamps = false;
    public $incrementing = true;

    /**
     * Get the base synchronization parameters for lookup entities.
     *
     * @return SyncParameter[] List of base synchronization parameters.
     */
    public static function baseParameters(): array
    {
        return [
            SyncParameter::createPrimaryKeyInteger(self::ATTR_ID, 1),
            SyncParameter::createTimestamp(self::ATTR_SYNC_DELETED_AT, 1)
        ];
    }

    /**
     * Get the table name for lookup entities.
     *
     * @return string Table name for lookup entities.
     */
    final public static function getTableName(): string
    {
        return "sel_" . static::getEntityName();
    }
}
