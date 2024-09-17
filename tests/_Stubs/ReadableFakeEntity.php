<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\ReadableEntitySynchronizable;

class ReadableFakeEntity extends ReadableEntitySynchronizable
{

    public static function parameters(): array
    {
        return [
            SyncParameter::createString("name", 1)
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