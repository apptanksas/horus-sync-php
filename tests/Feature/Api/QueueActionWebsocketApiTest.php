<?php

namespace Tests\Feature\Api;

use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use AppTank\Horus\Illuminate\Websocket\QueueActionBroadcast;
use AppTank\Horus\Illuminate\Websocket\QueueActionWebsocket;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\FeatureName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\_Stubs\SyncQueueActionModelFactory;

class QueueActionWebsocketApiTest extends ApiTestCase
{
    use RefreshDatabase;
    function testSubscriptionWithCheckpointReplaysActionsAfterEventId(): void
    {
        $userId = $this->faker->uuid;
        $checkpointEventId = $this->faker->uuid;
        $firstEventId = $this->faker->uuid;
        $lastEventId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        SyncQueueActionModelFactory::create($userId, [
            SyncQueueActionModel::ATTR_EVENT_ID => $checkpointEventId,
        ]);
        SyncQueueActionModelFactory::create($userId, [
            SyncQueueActionModel::ATTR_EVENT_ID => $firstEventId,
        ]);
        SyncQueueActionModelFactory::create($userId, [
            SyncQueueActionModel::ATTR_EVENT_ID => $lastEventId,
        ]);
        Event::fake();

        $response = $this->post('/horus/v1/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-' . QueueActionWebsocket::channelName($userId),
            'checkpoint_event_id' => $checkpointEventId,
        ]);

        $response->assertOk();
        Event::assertDispatched(QueueActionBroadcast::class, 2);
        Event::assertDispatched(QueueActionBroadcast::class, function (QueueActionBroadcast $event) use ($firstEventId, $lastEventId) {
            return in_array($event->broadcastWith()['event_id'], [$firstEventId, $lastEventId], true);
        });
    }

    function testSubscriptionWithoutCheckpointReplaysAllOwnerActions(): void
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        SyncQueueActionModelFactory::create($userId, [
            SyncQueueActionModel::ATTR_EVENT_ID => $this->faker->uuid,
        ]);
        SyncQueueActionModelFactory::create($userId, [
            SyncQueueActionModel::ATTR_EVENT_ID => $this->faker->uuid,
        ]);
        Event::fake();

        $response = $this->post('/horus/v1/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-' . QueueActionWebsocket::channelName($userId),
        ]);

        $response->assertOk();
        Event::assertDispatched(QueueActionBroadcast::class, 2);
    }

    function testSubscriptionIsRejectedWhenWebsocketFeatureIsDisabled(): void
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(disabledFeatures: [FeatureName::WEBSOCKET]));

        $response = $this->post('/horus/v1/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-' . QueueActionWebsocket::channelName($userId),
        ]);

        $response->assertForbidden();
    }
}