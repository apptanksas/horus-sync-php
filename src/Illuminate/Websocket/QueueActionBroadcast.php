<?php

namespace AppTank\Horus\Illuminate\Websocket;

use AppTank\Horus\Core\Model\QueueAction;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\PrivateChannel;

class QueueActionBroadcast implements ShouldBroadcastNow
{
    public function __construct(
        private readonly QueueAction $action,
    )
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(QueueActionWebsocket::channelName($this->action->ownerId));
    }

    public function broadcastAs(): string
    {
        return 'horus.sync.action';
    }

    public function broadcastWith(): array
    {
        return QueueActionWebsocket::serializeAction($this->action);
    }
}