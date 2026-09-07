<?php

namespace AppTank\Horus\Core\Websocket;

use AppTank\Horus\Core\Model\QueueAction;

interface QueueActionWebsocketPublisher
{
    public function publish(QueueAction $action): void;
}