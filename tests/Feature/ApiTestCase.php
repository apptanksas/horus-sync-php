<?php

namespace Tests\Feature;


use AppTank\Horus\Illuminate\Provider\HorusServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class ApiTestCase extends TestCase
{
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            HorusServiceProvider::class
        ];
    }
}