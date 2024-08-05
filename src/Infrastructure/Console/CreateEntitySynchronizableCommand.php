<?php

namespace AppTank\Horus\Infrastructure\Console;


use Illuminate\Console\GeneratorCommand;

class CreateEntitySynchronizableCommand extends GeneratorCommand
{
    protected $name = 'sync:entity';
    protected $description = 'Create a new entity synchronizable class';

    protected $type = 'EntitySync';

    function getStub(): string
    {
        return __DIR__ . '/stubs/entity.stub';
    }
}