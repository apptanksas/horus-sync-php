<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

class ChildFakeEntityFactory
{
    public static function create(?string $parentId = null): ChildFakeEntity
    {
        $faker = \Faker\Factory::create();
        $data = self::newData($parentId);

        $data[EntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($data);
        $data[EntitySynchronizable::ATTR_SYNC_OWNER_ID] = $faker->uuid;

        $entity = new ChildFakeEntity($data);

        $entity->save();

        return $entity;
    }


    public static function newData(?string $parentId = null): array
    {
        $faker = \Faker\Factory::create();

        return [
            EntitySynchronizable::ATTR_ID => $faker->uuid,
            ChildFakeEntity::ATTR_BOOLEAN_VALUE => $faker->boolean,
            ChildFakeEntity::ATTR_INT_VALUE => $faker->randomNumber(),
            ChildFakeEntity::ATTR_FLOAT_VALUE => $faker->randomFloat(),
            ChildFakeEntity::ATTR_STRING_VALUE => $faker->word,
            ChildFakeEntity::ATTR_TIMESTAMP_VALUE => $faker->dateTime->getTimestamp(),
            ChildFakeEntity::ATTR_PRIMARY_INT_VALUE => $faker->randomNumber(),
            ChildFakeEntity::ATTR_PRIMARY_STRING_VALUE => $faker->uuid,
            ChildFakeEntity::FK_PARENT_ID => $parentId ?? $faker->uuid
        ];
    }
}