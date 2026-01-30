<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;

class ChildFakeEntityFactory
{
    public static function create(?string $parentId = null, string|int $userId = null, array $newData = []): ChildFakeWritableEntity
    {
        $faker = \Faker\Factory::create();
        $data = self::newData($parentId);

        $data = array_merge($data, $newData);

        $data[WritableEntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($data);
        $data[WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID] = $userId ?? $faker->uuid;

        $entity = new ChildFakeWritableEntity($data);

        $entity->save();

        return $entity;
    }


    public static function newData(?string $parentId = null): array
    {
        $faker = \Faker\Factory::create();

        return [
            WritableEntitySynchronizable::ATTR_ID => $faker->uuid,
            ChildFakeWritableEntity::ATTR_BOOLEAN_VALUE => $faker->boolean,
            ChildFakeWritableEntity::ATTR_INT_VALUE => $faker->randomNumber(),
            ChildFakeWritableEntity::ATTR_FLOAT_VALUE => $faker->randomFloat(),
            ChildFakeWritableEntity::ATTR_STRING_VALUE => $faker->word,
            ChildFakeWritableEntity::ATTR_TIMESTAMP_VALUE => $faker->dateTime->getTimestamp(),
            ChildFakeWritableEntity::ATTR_PRIMARY_INT_VALUE => rand(1, 99999999),
            ChildFakeWritableEntity::ATTR_PRIMARY_STRING_VALUE => $faker->uuid,
            ChildFakeWritableEntity::FK_PARENT_ID => $parentId ?? $faker->uuid
        ];
    }
}