<?php

namespace AppTank\Horus\Core\Bus;

/**
 * @internal Interface IEventBus
 *
 * Defines the contract for an event bus that can publish events.
 *
 * @author John Ospina
 * Year: 2024
 */
interface IEventBus
{
    /**
     * Publish an event to the event bus.
     *
     * @param string $name The name or type of the event to publish.
     * @param array $data The data associated with the event.
     * @return void
     */
    function publish(string $name, array $data): void;
}