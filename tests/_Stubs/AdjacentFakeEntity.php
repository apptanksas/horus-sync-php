<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

class AdjacentFakeEntity extends EntitySynchronizable
{

    const string FK_PARENT_ID = "parent_id";

    public static function parameters(): array
    {
        return [
            SyncParameter::createString("name", 1),
            SyncParameter::createUUID(self::FK_PARENT_ID, 1)
        ];
    }

    public static function getEntityName(): string
    {
        return "adjacent_fake_entity";
    }

    public static function getVersionNumber(): int
    {
        return 1;
    }
}