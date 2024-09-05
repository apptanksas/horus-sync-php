<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\BaseSynchronizable;
use AppTank\Horus\Illuminate\Database\EntityDependsOn;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

class AdjacentFakeEntity extends EntitySynchronizable implements EntityDependsOn
{

    const string FK_PARENT_ID = "parent_id";

    public static function parameters(): array
    {
        return [
            SyncParameter::createString("name", 1),
            SyncParameter::createUUIDForeignKey(self::FK_PARENT_ID, 1, ParentFakeEntity::getEntityName())
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

    public function dependsOn(): IEntitySynchronizable
    {
        return $this->belongsTo(ParentFakeEntity::class,
            self::FK_PARENT_ID,
            BaseSynchronizable::ATTR_ID)->first();
    }
}