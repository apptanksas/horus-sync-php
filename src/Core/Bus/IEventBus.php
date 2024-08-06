<?php

namespace AppTank\Horus\Core\Bus;

interface IEventBus
{
    function publish(string $name, array $data): void;
}