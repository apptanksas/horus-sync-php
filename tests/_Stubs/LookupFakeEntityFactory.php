<?php

namespace Tests\_Stubs;

class LookupFakeEntityFactory
{
    function create(): LookupFakeEntity
    {
        $faker = \Faker\Factory::create();

        $entity = new LookupFakeEntity();
        $entity->save(["name"=>$faker->uuid]);

        return $entity;
    }
}