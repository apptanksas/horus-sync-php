<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\LookupSynchronizable;

class LookupFakeEntity extends LookupSynchronizable
{

    public static function parameters(): array
    {
        return [
            SyncParameter::createString("name", 1)
        ];
    }

    public static function getEntityName(): string
    {
        return "lookup_table";
    }

    public static function getVersionNumber(): int
    {
        return 1;
    }
}