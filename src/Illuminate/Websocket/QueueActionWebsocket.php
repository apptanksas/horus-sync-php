<?php

namespace AppTank\Horus\Illuminate\Websocket;

use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\Websocket\QueueActionWebsocketPublisher;
use Illuminate\Support\Facades\Event;

class QueueActionWebsocket implements QueueActionWebsocketPublisher
{
    public const CHANNEL = 'horus.sync';

    public function __construct(
        private readonly QueueActionRepository $queueActionRepository,
    )
    {
    }

    public static function channelName(string|int $ownerId): string
    {
        return self::CHANNEL . '.' . $ownerId;
    }

    public function publish(QueueAction $action): void
    {
        Event::dispatch(new QueueActionBroadcast($action));
    }

    public static function serializeAction(QueueAction $action): array
    {
        return [
            'event_id' => $action->eventId,
            'sequence' => $action->sequence,
            'action' => $action->action->name,
            'entity' => $action->entity,
            'data' => $action->operation->toArray(),
            'actioned_at' => $action->actionedAt->getTimestamp(),
            'synced_at' => $action->syncedAt->getTimestamp(),
        ];
    }
}