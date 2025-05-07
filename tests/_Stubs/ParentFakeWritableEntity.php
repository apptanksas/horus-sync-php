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

    const string ATTR_TIMESTAMP = "timestamp";

    const string ATTR_VALUE_NULLABLE = "value_nullable";

    const string ATTR_ENUM = "value_enum";

    const string ATTR_IMAGE = "image";

    const string ATTR_CUSTOM = "custom";

    const int VERSION_DEFAULT = 1;

    const int VERSION_CHILDREN = 2;

    const array ENUM_VALUES = ["value1", "value2", "value3"];

    const string REGEX_CUSTOM = "^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$";

    public static function parameters(): array
    {
        return [
            SyncParameter::createString(self::ATTR_NAME, self::VERSION_DEFAULT),
            SyncParameter::createString(self::ATTR_COLOR, self::VERSION_DEFAULT),
            SyncParameter::createTimestamp(self::ATTR_TIMESTAMP, self::VERSION_DEFAULT),
            SyncParameter::createString(self::ATTR_VALUE_NULLABLE, self::VERSION_DEFAULT, true),
            SyncParameter::createEnum(self::ATTR_ENUM, self::ENUM_VALUES, self::VERSION_DEFAULT),
            SyncParameter::createRelationOneOfMany([ChildFakeWritableEntity::class], self::VERSION_CHILDREN),
            SyncParameter::createRelationOneOfOne([AdjacentFakeWritableEntity::class], self::VERSION_CHILDREN),
            SyncParameter::createReferenceFile(self::ATTR_IMAGE, self::VERSION_DEFAULT, true),
            SyncParameter::createCustom(self::ATTR_CUSTOM, self::REGEX_CUSTOM, self::VERSION_DEFAULT, true),
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

    public static function getColumnIndexes(): array
    {
        return ["timestamp","color"];
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChildFakeWritableEntity::class, ChildFakeWritableEntity::FK_PARENT_ID, self::ATTR_ID);
    }

    public function adjacent(): HasOne
    {
        return $this->hasOne(AdjacentFakeWritableEntity::class, AdjacentFakeWritableEntity::FK_PARENT_ID, self::ATTR_ID);
    }

}