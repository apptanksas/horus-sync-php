<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Entity\EntityDependsOn;
use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;

class NestedChildFakeWritableEntity extends WritableEntitySynchronizable implements EntityDependsOn
{

    const FK_CHILD_ID = "child_id";

    const VERSION_ATTRIBUTES = 1;

    protected $fillable = [
        self::FK_CHILD_ID,
        self::ATTR_SYNC_HASH,
        self::ATTR_SYNC_OWNER_ID,
        self::ATTR_SYNC_CREATED_AT,
        self::ATTR_SYNC_UPDATED_AT,
    ];

    public static function parameters(): array
    {
        return [
            SyncParameter::createUUIDForeignKey(self::FK_CHILD_ID, self::VERSION_ATTRIBUTES, ChildFakeWritableEntity::getEntityName(), true)
        ];
    }

    public static function getEntityName(): string
    {
        return "nested_child_fake_entity";
    }

    public static function getVersionNumber(): int
    {
        return 1;
    }


    public function dependsOn(): IEntitySynchronizable
    {
        return $this->belongsTo(ChildFakeWritableEntity::class, self::FK_CHILD_ID, EntitySynchronizable::ATTR_ID)->first();
    }

    public function getEntityParentId(): string|null
    {
        return $this->getAttribute(self::FK_CHILD_ID);
    }

    public function getEntityParentParameterName(): string
    {
        return self::FK_CHILD_ID;
    }
}