<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

class AdjacentFakeEntityFactory
{
    public static function create(?string $parentId = null, string|int $userId = null)
    {
        $faker = \Faker\Factory::create();
        $data = self::newData($parentId);

        $data[EntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($data);
        $data[EntitySynchronizable::ATTR_SYNC_OWNER_ID] = $userId ?? $faker->uuid;

        $entity = new AdjacentFakeEntity($data);
        $entity->save();

        return $entity;
    }

    public static function newData(?string $parentId = null): array
    {
        $faker = \Faker\Factory::create();

        return [
            EntitySynchronizable::ATTR_ID => $faker->uuid,
            "name" => $faker->word,
            AdjacentFakeEntity::FK_PARENT_ID => $parentId ?? $faker->uuid
        ];
    }
}