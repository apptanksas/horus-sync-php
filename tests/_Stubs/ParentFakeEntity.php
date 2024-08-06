<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

class ParentFakeEntity extends EntitySynchronizable
{

    const ATTR_NAME = "name";
    const ATTR_COLOR = "color";

    const RELATED_CHILDREN = "children";

    const VERSION_NAME = 1;

    const VERSION_COLOR = 2;

    const VERSION_CHILDREN = 2;

    public static function parameters(): array
    {
        return [
            SyncParameter::createString(self::ATTR_NAME, self::VERSION_NAME),
            SyncParameter::createTimestamp(self::ATTR_COLOR, self::VERSION_COLOR),
            SyncParameter::createRelationOneToMany(self::RELATED_CHILDREN, [ChildFakeEntity::class], self::VERSION_CHILDREN)
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
}