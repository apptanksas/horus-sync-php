<?php

namespace AppTank\Horus\Illuminate\Bus;

use AppTank\Horus\Core\Bus\IEventBus;
use Illuminate\Support\Facades\Event;

/**
 * @internal Class EventBus
 *
 * Implements the IEventBus interface for publishing events using Laravel's event dispatcher.
 *
 * This class utilizes Laravel's built-in Event facade to dispatch events with a specific name and associated data.
 *
 * @package AppTank\Horus\Illuminate\Bus
 */
class EventBus implements IEventBus
{
    /**
     * Publish an event with the specified name and data.
     *
     * This method uses Laravel's Event facade to dispatch an event. The event name is prefixed with "horus." and
     * the provided data is passed to the event listener.
     *
     * @param string $name The name of the event to dispatch.
     * @param array $data The data to pass to the event listener.
     *
     * @return void
     */
    public function publish(string $name, array $data): void
    {
        Event::dispatch("horus.$name", $data);
    }
}
