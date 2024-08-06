<?php

namespace Tests;


use AppTank\Horus\HorusContainer;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Concerns\WithWorkbench;

class TestCase extends \Orchestra\Testbench\TestCase
{

    use WithLaravelMigrations, WithWorkbench, LazilyRefreshDatabase;

    protected bool $initializeContainer = true;

    function setUp(): void
    {
        if ($this->initializeContainer) {
            HorusContainer::initialize([]);
        }
        parent::setUp();
    }

    protected function defineEnvironment($app): void
    {
        $config = $app->make(Repository::class);

        $config->set([
            'database.default' => 'testing',
        ]);
    }

    protected function generateArray(callable $creator, int $maxQuantity = 12): array
    {

        $arrayData = [];
        $quantity = rand(1, $maxQuantity);

        for ($i = 0; $i < $quantity; $i++) {
            $arrayData[] = $creator($i);
        }

        return $arrayData;
    }

    protected function generateCountArray(callable $creator, int $quantity = 12): array
    {

        $arrayData = [];

        for ($i = 0; $i < $quantity; $i++) {
            $arrayData[] = $creator($i);
        }

        return $arrayData;
    }


}