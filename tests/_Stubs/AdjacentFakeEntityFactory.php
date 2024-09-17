<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;

class AdjacentFakeEntityFactory
{
    public static function create(?string $parentId = null, string|int $userId = null)
    {
        $faker = \Faker\Factory::create();
        $data = self::newData($parentId);

        $data[WritableEntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($data);
        $data[WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID] = $userId ?? $faker->uuid;

        $entity = new AdjacentFakeWritableEntity($data);
        $entity->save();

        return $entity;
    }

    public static function newData(?string $parentId = null): array
    {
        $faker = \Faker\Factory::create();

        return [
            WritableEntitySynchronizable::ATTR_ID => $faker->uuid,
            "name" => $faker->word,
            AdjacentFakeWritableEntity::FK_PARENT_ID => $parentId ?? $faker->uuid
        ];
    }
}