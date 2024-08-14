<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;

abstract class LookupSynchronizable extends BaseSynchronizable implements IEntitySynchronizable
{


    public $timestamps = false;

    public $incrementing = true;


    public static function baseParameters(): array
    {
        return [
            SyncParameter::createPrimaryKeyInteger(self::ATTR_ID, 1),
            SyncParameter::createTimestamp(self::ATTR_SYNC_DELETED_AT, 1)
        ];
    }

    final public static function getTableName(): string
    {
        return "sel_" . static::getEntityName();
    }





}