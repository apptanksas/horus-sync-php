<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParentFakeWritableEntity extends WritableEntitySynchronizable
{

    const string ATTR_NAME = "name";
    const string ATTR_COLOR = "color";

    const string ATTR_VALUE_NULLABLE = "value_nullable";

    const string ATTR_ENUM = "value_enum";

    const int VERSION_NAME = 1;

    const int VERSION_DEFAULT = 2;

    const int VERSION_CHILDREN = 2;

    const array ENUM_VALUES = ["value1", "value2", "value3"];

    public static function parameters(): array
    {
        return [
            SyncParameter::createString(self::ATTR_NAME, self::VERSION_NAME),
            SyncParameter::createTimestamp(self::ATTR_COLOR, self::VERSION_DEFAULT),
            SyncParameter::createString(self::ATTR_VALUE_NULLABLE, self::VERSION_DEFAULT, true),
            SyncParameter::createEnum(self::ATTR_ENUM, self::ENUM_VALUES, self::VERSION_DEFAULT),
            SyncParameter::createRelationOneOfMany([ChildFakeWritableEntity::class], self::VERSION_CHILDREN),
            SyncParameter::createRelationOneOfOne([AdjacentFakeWritableEntity::class], self::VERSION_CHILDREN)
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

    public function getRelationsOneOfMany(): array
    {
        return ["children"];
    }

    public function getRelationsOneOfOne(): array
    {
        return ["adjacent"];
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChildFakeWritableEntity::class, ChildFakeWritableEntity::FK_PARENT_ID, self::ATTR_ID);
    }

    public function adjacent():HasOne
    {
        return $this->hasOne(AdjacentFakeWritableEntity::class, AdjacentFakeWritableEntity::FK_PARENT_ID, self::ATTR_ID);
    }
}