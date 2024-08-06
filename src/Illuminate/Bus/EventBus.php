<?php

namespace AppTank\Horus\Illuminate\Bus;

use AppTank\Horus\Core\Bus\IEventBus;
use Illuminate\Support\Facades\Event;

class EventBus implements IEventBus
{

    function publish(string $name, array $data): void
    {
        Event::dispatch("horus.$name", $data);
    }
}