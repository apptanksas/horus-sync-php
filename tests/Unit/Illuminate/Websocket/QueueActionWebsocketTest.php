<?php

namespace Tests\Unit\Illuminate\Websocket;

use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Illuminate\Websocket\QueueActionBroadcast;
use AppTank\Horus\Illuminate\Websocket\QueueActionWebsocket;
use Illuminate\Support\Facades\Event;
use Tests\_Stubs\QueueActionFactory;
use Tests\TestCase;

class QueueActionWebsocketTest extends TestCase
{
    function testReplayPublishesActionsAfterCheckpoint(): void
    {
        Event::fake();

        $ownerId = $this->faker->uuid;
        $checkpointEventId = $this->faker->uuid;
        $actions = [
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid),
            QueueActionFactory::create(userId: $ownerId, eventId: $this->faker->uuid),
        ];
        $repository = $this->mock(QueueActionRepository::class);
        $repository->shouldReceive('getActions')
            ->once()
            ->with([$ownerId], null, [], [], $checkpointEventId, [])
            ->andReturn($actions);

        $websocket = new QueueActionWebsocket($repository);

        $result = $websocket->replay($ownerId, $checkpointEventId);

        $this->assertSame($actions, $result);
        Event::assertDispatched(QueueActionBroadcast::class, 2);
    }

    function testReplayWithoutCheckpointPublishesAllOwnerActions(): void
    {
        Event::fake();

        $ownerId = $this->faker->uuid;
        $action = QueueActionFactory::create(userId: $ownerId);
        $repository = $this->mock(QueueActionRepository::class);
        $repository->shouldReceive('getActions')
            ->once()
            ->with([$ownerId], null, [], [], null, [])
            ->andReturn([$action]);

        $websocket = new QueueActionWebsocket($repository);

        $websocket->replay($ownerId);

        Event::assertDispatched(QueueActionBroadcast::class, function (QueueActionBroadcast $event) use ($action) {
            return $event->broadcastWith()['event_id'] === $action->eventId;
        });
    }

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