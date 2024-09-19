<?php

namespace AppTank\Horus\Illuminate\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @internal Class CreateEntitySynchronizableCommand
 *
 * A custom Artisan command for generating a new entity synchronizable class.
 *
 * This command extends Laravel's `GeneratorCommand` to create a new class that is intended for synchronizing entities.
 * It provides a stub file for the class definition and specifies the path where the new class file should be created.
 *
 * @package AppTank\Horus\Illuminate\Console
 */
class CreateEntitySynchronizableCommand extends GeneratorCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'horus:entity';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Create a new entity synchronizable class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'EntitySync';

    /**
     * Get the stub file for the generator.
     *
     * This method returns the path to the stub file used for generating the new class.
     *
     * @return string
     */
    protected function getStub(): string
    {
        if ($this->hasOption("readable")) {
            return __DIR__ . '/stubs/readable_entity.stub';
        }
        return __DIR__ . '/stubs/writable_entity.stub';
    }

    /**
     * Get the path to the new class file.
     *
     * This method determines the path where the new class file will be created based on the provided name.
     *
     * @param string $name The name of the new class.
     * @return string
     */
    protected function getPath($name): string
    {
        return parent::getPath("Models/Sync/" . $name);
    }

    /**
     * Get the options for the command.
     *
     * @return array[]
     */
    protected function getOptions(): array
    {
        return [
            ['readable', null, InputOption::VALUE_NONE, 'Generate a readable entity.'],
        ];
    }
}
