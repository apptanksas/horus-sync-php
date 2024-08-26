<?php

namespace Tests;


use AppTank\Horus\Horus;
use Faker\Generator;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Tests\_Stubs\AdjacentFakeEntity;
use Tests\_Stubs\ChildFakeEntity;
use Tests\_Stubs\LookupFakeEntity;
use Tests\_Stubs\ParentFakeEntity;

class TestCase extends \Orchestra\Testbench\TestCase
{

    use WithLaravelMigrations, WithWorkbench, LazilyRefreshDatabase;

    protected Generator $faker;

    function setUp(): void
    {
        Horus::initialize([
            ParentFakeEntity::class => [
                ChildFakeEntity::class,
                AdjacentFakeEntity::class
            ],
            LookupFakeEntity::class
        ]);

        parent::setUp();
        $this->faker = \Faker\Factory::create();
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