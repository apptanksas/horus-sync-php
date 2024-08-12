<?php

namespace AppTank\Horus\Illuminate\Console;


use Illuminate\Console\GeneratorCommand;

class CreateEntitySynchronizableCommand extends GeneratorCommand
{
    protected $name = 'horus:entity';
    protected $description = 'Create a new entity synchronizable class';

    protected $type = 'EntitySync';

    function getStub(): string
    {
        return __DIR__ . '/stubs/entity.stub';
    }

    function getPath($name): string
    {
       return parent::getPath("Models/Sync/" . $name);
    }
}