<?php

namespace Tests\_Stubs;

class LookupFakeEntityFactory
{
    public static function create(): LookupFakeEntity
    {

        $data = self::newData();
        unset($data["id"]);
        $entity = new LookupFakeEntity($data);
        $entity->save();

        return $entity;
    }

    public static function newData(): array
    {
        $faker = \Faker\Factory::create();

        return [
            "id" => $faker->randomNumber(),
            "name" => $faker->name
        ];
    }

}