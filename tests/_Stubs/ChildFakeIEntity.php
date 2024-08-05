<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

class ChildFakeIEntity extends EntitySynchronizable
{

    const ATTR_PRIMARY_INT_VALUE = "primary_int_value";
    const ATTR_PRIMARY_STRING_VALUE = "primary_string_value";
    const ATTR_INT_VALUE = "int_value";
    const ATTR_FLOAT_VALUE = "float_value";
    const ATTR_STRING_VALUE = "string_value";
    const ATTR_BOOLEAN_VALUE = "boolean_value";
    const ATTR_TIMESTAMP_VALUE = "timestamp_value";

    const VERSION_ATTRIBUTES = 5;

    public static function parameters(): array
    {
        return [
            SyncParameter::createPrimaryKeyInteger(self::ATTR_PRIMARY_INT_VALUE, self::VERSION_ATTRIBUTES),
            SyncParameter::createPrimaryKeyString(self::ATTR_PRIMARY_STRING_VALUE, self::VERSION_ATTRIBUTES),
            SyncParameter::createInt(self::ATTR_INT_VALUE, self::VERSION_ATTRIBUTES, true),
            SyncParameter::createFloat(self::ATTR_FLOAT_VALUE, self::VERSION_ATTRIBUTES),
            SyncParameter::createString(self::ATTR_STRING_VALUE, self::VERSION_ATTRIBUTES),
            SyncParameter::createBoolean(self::ATTR_BOOLEAN_VALUE, self::VERSION_ATTRIBUTES),
            SyncParameter::createTimestamp(self::ATTR_TIMESTAMP_VALUE, self::VERSION_ATTRIBUTES)
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
}