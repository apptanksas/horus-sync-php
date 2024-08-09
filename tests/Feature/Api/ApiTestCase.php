<?php

namespace Tests\Feature\Api;


use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Provider\HorusServiceProvider;
use Faker\Generator;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Tests\_Stubs\ChildFakeEntity;
use Tests\_Stubs\ParentFakeEntity;

class ApiTestCase extends TestCase
{
    use WithWorkbench;

    protected Generator $faker;
    protected function setUp(): void
    {
        HorusContainer::initialize([
            ParentFakeEntity::class => [
                ChildFakeEntity::class
            ]
        ]);

        parent::setUp();

        $this->faker = \Faker\Factory::create();
    }

    protected function getPackageProviders($app): array
    {
        return [
            HorusServiceProvider::class
        ];
    }
}