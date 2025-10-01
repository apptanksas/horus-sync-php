<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;

class ParentFakeEntityFactory
{
    public static function create(string|int $userId = null, array $data = array(), ?\DateTimeImmutable $deletedAt = null): ParentFakeWritableEntity
    {
        $faker = \Faker\Factory::create();
        $data = array_replace(self::newData(), $data);

        $data[WritableEntitySynchronizable::ATTR_SYNC_HASH] = Hasher::hash($data);
        $data[WritableEntitySynchronizable::ATTR_SYNC_OWNER_ID] = $userId ?? $faker->uuid;

        if ($deletedAt !== null) {
            $data[WritableEntitySynchronizable::ATTR_SYNC_DELETED_AT] = $deletedAt->format('Y-m-d H:i:s');
        }

        $entity = new ParentFakeWritableEntity($data);
        $entity->setTable(ParentFakeWritableEntity::getTableName());
        $entity->saveOrFail();

        return $entity;
    }


    public static function newData(?string $valueNullable = null): array
    {
        $faker = \Faker\Factory::create();

        return [
            WritableEntitySynchronizable::ATTR_ID => $faker->uuid,
            ParentFakeWritableEntity::ATTR_NAME => $faker->name,
            ParentFakeWritableEntity::ATTR_COLOR => $faker->colorName,
            ParentFakeWritableEntity::ATTR_TIMESTAMP => $faker->dateTimeBetween()->getTimestamp(),
            ParentFakeWritableEntity::ATTR_ENUM => ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)],
            ParentFakeWritableEntity::ATTR_VALUE_NULLABLE => $valueNullable ?? ($faker->boolean ? $faker->word : null),
            ParentFakeWritableEntity::ATTR_IMAGE => $faker->uuid
        ];
    }
}