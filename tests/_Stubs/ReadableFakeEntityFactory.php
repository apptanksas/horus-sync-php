<?php

namespace Tests\_Stubs;

class ReadableFakeEntityFactory
{
    public static function create(): ReadableFakeEntity
    {

        $data = self::newData();
        unset($data["id"]);
        $entity = new ReadableFakeEntity($data);
        $entity->save();

        return $entity;
    }

    public static function newData(): array
    {
        $faker = \Faker\Factory::create();

        return [
            "id" => $faker->randomNumber(),
            "name" => $faker->name,
            "type" => $faker->randomElement(["type1", "type2", "type3"]),
        ];
    }

}