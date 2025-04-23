<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\ReadableEntitySynchronizable;

class ReadableFakeEntity extends ReadableEntitySynchronizable
{

    const string ATTR_NAME = "name";
    const string ATTR_TYPE = "type";

    public static function parameters(): array
    {
        return [
            SyncParameter::createString("name", 1),
            SyncParameter::createString("type", 1, true)
        ];
    }

    public static function getEntityName(): string
    {
        return "readable_fake_entity";
    }

    public static function getVersionNumber(): int
    {
        return 1;
    }
}