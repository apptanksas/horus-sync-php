<?php

namespace Api;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\SyncQueueActionModelFactory;
use Tests\Feature\Api\ApiTestCase;

class GetSyncQueueActionsApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private const array JSON_SCHEME = [
        '*' => [
            'action',
            'entity',
            'data',
            'actioned_at',
            'synced_at'
        ]
    ];

    function testGetActionsIsSuccess()
    {

        // Given
        $userId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $actions = $this->generateArray(fn() => SyncQueueActionModelFactory::create(userId: $userId));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_ACTIONS->value));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($actions));
        $response->assertExactJsonStructure(self::JSON_SCHEME);
    }

    function testGetActionsAfterTimestampIsSuccess()
    {
        $ownerId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($ownerId));

        $syncedAt = $this->faker->dateTimeBetween()->getTimestamp();
        /**
         * @var SyncQueueActionModel[] $actions
         */
        $actions = $this->generateArray(fn() => SyncQueueActionModelFactory::create($ownerId, [
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($syncedAt)
        ]));

        // Generate entities before the updatedAt
        $this->generateArray(fn() => SyncQueueActionModelFactory::create($ownerId, [
            SyncQueueActionModel::ATTR_SYNCED_AT => $this->getDateTimeUtil()->getFormatDate($this->faker->dateTimeBetween(endDate: $syncedAt)->getTimestamp())
        ]));

        $syncedAtTarget = $syncedAt - 1;
        $countExpected = count(array_filter($actions, fn(SyncQueueActionModel $entity) => $entity->getSyncedAt()->getTimestamp() > $syncedAtTarget));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_ACTIONS->value, ["after" => $syncedAtTarget]));

        // Then
        $response->assertOk();
        $response->assertJsonCount($countExpected);
        $response->assertExactJsonStructure(self::JSON_SCHEME);
    }

    function testGetActionsFilterDateTimes()
    {
        $ownerId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($ownerId));
        /**
         * @var SyncQueueActionModel[] $parentsEntities
         */
        $actions = $this->generateCountArray(fn() => SyncQueueActionModelFactory::create($ownerId, [
            SyncQueueActionModel::ATTR_ACTIONED_AT => $this->getDateTimeUtil()->getFormatDate($this->faker->dateTimeBetween()->getTimestamp())
        ]));

        $excludeActions = array_map(fn(SyncQueueActionModel $entity) => $entity->getActionedAt()->getTimestamp(), array_slice($actions, 0, rand(1, 5)));
        $countExpected = count($actions) - count($excludeActions);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_ACTIONS->value, ["exclude" => join(",", $excludeActions)]));

        // Then
        $response->assertOk();
        $response->assertJsonCount($countExpected);
        $response->assertExactJsonStructure(self::JSON_SCHEME);
    }
}