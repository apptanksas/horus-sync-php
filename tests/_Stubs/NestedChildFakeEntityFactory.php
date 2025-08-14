<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;

class NestedChildFakeEntityFactory
{
    public static function create(?string $parentId = null, string|int $userId = null): NestedChildFakeWritableEntity
    {
        $faker = \Faker\Factory::create();
        $data = self::newData($parentId);

        $data[WritableEntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($data);
        $data[WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID] = $userId ?? $faker->uuid;

        $entity = new NestedChildFakeWritableEntity($data);

        $entity->save();

        return $entity;
    }


    public static function newData(?string $childId = null): array
    {
        $faker = \Faker\Factory::create();

        return [
            WritableEntitySynchronizable::ATTR_ID => $faker->uuid,
            NestedChildFakeWritableEntity::FK_CHILD_ID => $childId ?? $faker->uuid,
        ];
    }
}