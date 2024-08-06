<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\SyncAction;
use Faker\Guesser\Name;

class QueueActionStub
{


    public static function create(array $data = []): QueueAction
    {
        $faker = \Faker\Factory::create();

        if (empty($data)) {
            for ($i = 0; $i < rand(1, 10); $i++) {
                $data[$faker->colorName] = $faker->word;
            }
        }

        return new QueueAction(
            SyncAction::random(),
            $faker->userName,
            $data,
            now()->toDateTimeImmutable(),
            now()->toDateTimeImmutable(),
            $faker->uuid,
            $faker->uuid
        );
    }
}