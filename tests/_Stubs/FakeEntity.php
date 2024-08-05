<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\EntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;

class FakeEntity extends EntitySynchronizable
{

    protected static function parameters(): array
    {
        return [
            SyncParameter::createString("name", 1),
            SyncParameter::createTimestamp("attr", 2)
        ];
    }

    protected static function getEntityName(): string
    {
        return "fake_entity";
    }

    protected static function getVersionNumber(): int
    {
        return 1;
    }
}