<?php

namespace Tests\Unit\Illuminate\Websocket;

use AppTank\Horus\Illuminate\Websocket\QueueActionBroadcast;
use Tests\_Stubs\QueueActionFactory;
use Tests\TestCase;

class QueueActionWebsocketTest extends TestCase
{

    function testBroadcastUsesTheOwnerChannelAndSerializesTheCheckpoint(): void
    {
        $action = QueueActionFactory::create(
            userId: $this->faker->uuid,
            eventId: $this->faker->uuid,
        );
        $broadcast = new QueueActionBroadcast($action);

        $this->assertSame(
            'private-horus.sync.' . $action->ownerId,
            $broadcast->broadcastOn()->name,
        );
        $this->assertSame('horus.sync.action', $broadcast->broadcastAs());
        $this->assertSame($action->eventId, $broadcast->broadcastWith()['event_id']);
    }
}