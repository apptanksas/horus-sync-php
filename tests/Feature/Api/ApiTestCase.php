<?php

namespace Tests\Feature\Api;


use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Provider\HorusServiceProvider;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use Faker\Generator;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Tests\_Stubs\AdjacentFakeEntity;
use Tests\_Stubs\ChildFakeEntity;
use Tests\_Stubs\LookupFakeEntity;
use Tests\_Stubs\ParentFakeEntity;

class ApiTestCase extends TestCase
{
    use WithWorkbench;

    protected Generator $faker;

    protected array $middlewares = [];

    protected function setUp(): void
    {
        Horus::initialize([
            ParentFakeEntity::class => [
                ChildFakeEntity::class,
                AdjacentFakeEntity::class
            ],
            LookupFakeEntity::class
        ]);

        Horus::setMiddlewares($this->middlewares);

        parent::setUp();

        $this->faker = \Faker\Factory::create();
    }

    protected function getPackageProviders($app): array
    {
        return [
            HorusServiceProvider::class
        ];
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

    protected function getDateTimeUtil(): DateTimeUtil
    {
        return new DateTimeUtil();
    }

}