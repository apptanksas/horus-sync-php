<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

class ParentFakeEntityFactory
{
    public static function create(): ParentFakeEntity
    {
        $faker = \Faker\Factory::create();
        $data = self::newData();

        $data[EntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($data);
        $data[EntitySynchronizable::ATTR_SYNC_OWNER_ID] = $faker->uuid;

        $entity = new ParentFakeEntity($data);
        $entity->setTable(ParentFakeEntity::getTableName());
        $entity->saveOrFail();

        return $entity;
    }


    public static function newData(): array
    {
        $faker = \Faker\Factory::create();

        return [
            EntitySynchronizable::ATTR_ID => $faker->uuid,
            ParentFakeEntity::ATTR_NAME => $faker->name,
            ParentFakeEntity::ATTR_COLOR => $faker->colorName
        ];
    }
}