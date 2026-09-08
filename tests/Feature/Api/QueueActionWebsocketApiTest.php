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

    function testBroadcastAuthenticationSupportsHorusUserForNonPrivateChannel(): void
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $response = $this->post('/horus/v1/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => QueueActionWebsocket::channelName($userId),
        ]);

        $response->assertOk();
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