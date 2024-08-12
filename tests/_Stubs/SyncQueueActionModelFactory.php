<?php

namespace Tests\_Stubs;

use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;

class SyncQueueActionModelFactory
{
    public static function create(string|int $userId = null, array $data = array()): SyncQueueActionModel
    {
        $faker = \Faker\Factory::create();
        $dataOperation = ["id" => $faker->uuid, "name" => $faker->name];
        $action = SyncAction::random();

        if ($action == SyncAction::UPDATE) {
            $dataOperation["attributes"] = ["color" => "red", "size" => "large"];
        }

        $data = array_replace([
            SyncQueueActionModel::ATTR_ACTION => $action->value,
            SyncQueueActionModel::ATTR_ENTITY => $faker->userName,
            SyncQueueActionModel::ATTR_DATA => json_encode($dataOperation),
            SyncQueueActionModel::ATTR_ACTIONED_AT => now()->format('Y-m-d H:i:s'),
            SyncQueueActionModel::ATTR_SYNCED_AT => $faker->dateTimeBetween()->format('Y-m-d H:i:s'),
            SyncQueueActionModel::FK_USER_ID => $userId ?? $faker->uuid,
            SyncQueueActionModel::FK_OWNER_ID => $userId ?? $faker->uuid,
        ], $data);


        $model = new SyncQueueActionModel($data);
        $model->saveOrFail();

        return $model;
    }

}