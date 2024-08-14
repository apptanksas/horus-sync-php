<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentFakeEntity extends EntitySynchronizable
{

    const ATTR_NAME = "name";
    const ATTR_COLOR = "color";

    const ATTR_VALUE_NULLABLE = "value_nullable";

    const ATTR_ENUM = "value_enum";

    const VERSION_NAME = 1;

    const VERSION_DEFAULT = 2;

    const VERSION_CHILDREN = 2;

    const ENUM_VALUES = ["value1", "value2", "value3"];

    public static function parameters(): array
    {
        return [
            SyncParameter::createString(self::ATTR_NAME, self::VERSION_NAME),
            SyncParameter::createTimestamp(self::ATTR_COLOR, self::VERSION_DEFAULT),
            SyncParameter::createString(self::ATTR_VALUE_NULLABLE, self::VERSION_DEFAULT, true),
            SyncParameter::createEnum(self::ATTR_ENUM, self::ENUM_VALUES, self::VERSION_DEFAULT),
            SyncParameter::createRelationOneOfMany([ChildFakeEntity::class], self::VERSION_CHILDREN)
        ];
    }

    public static function getEntityName(): string
    {
        return "parent_fake_entity";
    }

    public static function getVersionNumber(): int
    {
        return 2;
    }

    public function getRelationsMany(): array
    {
        return ["children"];
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChildFakeEntity::class, ChildFakeEntity::FK_PARENT_ID, self::ATTR_ID);
    }
}