<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\SyncAction;
use Carbon\Carbon;
use Faker\Guesser\Name;

class QueueActionFactory
{


    public static function create(?EntityOperation $entityOperation = null, ?string $userId = null, bool $bySystem = false): QueueAction
    {

        $faker = \Faker\Factory::create();
        $action = SyncAction::random();

        $entity = $faker->userName;
        $operation = $entityOperation ?? self::createEntityOperation($entity, $action, $userId);

        return new QueueAction(
            $action,
            $entityOperation?->entity ?? $faker->userName,
            $operation->id,
            $operation,
            Carbon::create($faker->dateTimeBetween)->toDateTimeImmutable(),
            now()->toDateTimeImmutable(),
            $userId ?? $faker->uuid,
            $userId ?? $faker->uuid,
            $bySystem
        );
    }


    private static function createEntityOperation(string $entity, SyncAction $action, ?string $userId = null): EntityOperation
    {
        $faker = \Faker\Factory::create();
        $data = ["id" => $faker->uuid];

        for ($i = 0; $i < rand(1, 10); $i++) {
            $data[$faker->colorName] = $faker->word;
        }

        return match ($action) {
            SyncAction::INSERT => new EntityInsert($userId ?? $faker->uuid, $entity, now()->toDateTimeImmutable(), $data),
            SyncAction::UPDATE => new EntityUpdate($userId ?? $faker->uuid, $entity, $faker->uuid, now()->toDateTimeImmutable(), $data),
            SyncAction::DELETE => new EntityDelete($userId ?? $faker->uuid, $entity, $faker->uuid, now()->toDateTimeImmutable()),
        };
    }
}