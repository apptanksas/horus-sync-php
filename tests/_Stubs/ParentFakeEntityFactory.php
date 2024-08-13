<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

class ParentFakeEntityFactory
{
    public static function create(string|int $userId = null, array $data = array()): ParentFakeEntity
    {
        $faker = \Faker\Factory::create();
        $data = array_replace(self::newData(), $data);

        $data[EntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($data);
        $data[EntitySynchronizable::ATTR_SYNC_OWNER_ID] = $userId ?? $faker->uuid;

        $entity = new ParentFakeEntity($data);
        $entity->setTable(ParentFakeEntity::getTableName());
        $entity->saveOrFail();

        return $entity;
    }


    public static function newData(?string $valueNullable = null): array
    {
        $faker = \Faker\Factory::create();

        return [
            EntitySynchronizable::ATTR_ID => $faker->uuid,
            ParentFakeEntity::ATTR_NAME => $faker->name,
            ParentFakeEntity::ATTR_COLOR => $faker->colorName,
            ParentFakeEntity::ATTR_ENUM => ParentFakeEntity::ENUM_VALUES[array_rand(ParentFakeEntity::ENUM_VALUES)],
            ParentFakeEntity::ATTR_VALUE_NULLABLE => $valueNullable ?? ($faker->boolean ? $faker->word : null),
        ];
    }
}