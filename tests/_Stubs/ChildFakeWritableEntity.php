<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\EntityDependsOn;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;

class ChildFakeWritableEntity extends WritableEntitySynchronizable implements EntityDependsOn
{

    const ATTR_PRIMARY_INT_VALUE = "primary_int_value";
    const ATTR_PRIMARY_STRING_VALUE = "primary_string_value";
    const ATTR_INT_VALUE = "int_value";
    const ATTR_FLOAT_VALUE = "float_value";
    const ATTR_STRING_VALUE = "string_value";
    const ATTR_BOOLEAN_VALUE = "boolean_value";
    const ATTR_TIMESTAMP_VALUE = "timestamp_value";

    const FK_PARENT_ID = "parent_id";

    const VERSION_ATTRIBUTES = 5;

    protected $fillable = [
        self::ATTR_ID,
        self::ATTR_PRIMARY_INT_VALUE,
        self::ATTR_PRIMARY_STRING_VALUE,
        self::ATTR_INT_VALUE,
        self::ATTR_FLOAT_VALUE,
        self::ATTR_STRING_VALUE,
        self::ATTR_BOOLEAN_VALUE,
        self::ATTR_TIMESTAMP_VALUE,
        self::FK_PARENT_ID,
        self::ATTR_SYNC_HASH,
        self::ATTR_SYNC_OWNER_ID,
        self::ATTR_SYNC_CREATED_AT,
        self::ATTR_SYNC_UPDATED_AT,
    ];

    public static function parameters(): array
    {
        return [
            SyncParameter::createPrimaryKeyInteger(self::ATTR_PRIMARY_INT_VALUE, self::VERSION_ATTRIBUTES),
            SyncParameter::createPrimaryKeyString(self::ATTR_PRIMARY_STRING_VALUE, self::VERSION_ATTRIBUTES),
            SyncParameter::createInt(self::ATTR_INT_VALUE, self::VERSION_ATTRIBUTES, true),
            new SyncParameter(
                self::ATTR_FLOAT_VALUE,
                SyncParameterType::FLOAT,
                self::VERSION_ATTRIBUTES,
                withIndex: true,
            ),
            SyncParameter::createString(self::ATTR_STRING_VALUE, self::VERSION_ATTRIBUTES),
            SyncParameter::createBoolean(self::ATTR_BOOLEAN_VALUE, self::VERSION_ATTRIBUTES),
            SyncParameter::createTimestamp(self::ATTR_TIMESTAMP_VALUE, self::VERSION_ATTRIBUTES),
            SyncParameter::createUUIDForeignKey(self::FK_PARENT_ID, self::VERSION_ATTRIBUTES, ParentFakeWritableEntity::getEntityName(), true)
        ];
    }

    public static function getEntityName(): string
    {
        return "child_fake_entity";
    }

    public static function getVersionNumber(): int
    {
        return 5;
    }


    public function dependsOn(): IEntitySynchronizable
    {
        return $this->belongsTo(ParentFakeWritableEntity::class, self::FK_PARENT_ID, EntitySynchronizable::ATTR_ID)->first();
    }
}