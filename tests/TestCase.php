<?php

namespace Tests;


use Illuminate\Config\Repository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Concerns\WithWorkbench;

class TestCase extends \Orchestra\Testbench\TestCase
{

    use WithLaravelMigrations, WithWorkbench, LazilyRefreshDatabase;


    function setUp(): void
    {
        parent::setUp();
    }

    protected function defineEnvironment($app): void
    {
        $config = $app->make(Repository::class);

        $config->set([
            'database.default' => 'testing',
        ]);
    }
}