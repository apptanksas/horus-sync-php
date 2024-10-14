<?php

namespace Tests;


use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use Faker\Generator;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\Mock;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Tests\_Stubs\AdjacentFakeWritableEntity;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\ReadableFakeEntity;
use Tests\_Stubs\ParentFakeWritableEntity;

class TestCase extends \Orchestra\Testbench\TestCase
{

    use WithLaravelMigrations, WithWorkbench, LazilyRefreshDatabase;

    protected Generator $faker;
    private IFileHandler|Mock $fileHandler;


    function setUp(): void
    {
        $this->fileHandler = \Mockery::mock(IFileHandler::class);

        Horus::initialize([
            ParentFakeWritableEntity::class => [
                ChildFakeWritableEntity::class,
                AdjacentFakeWritableEntity::class
            ],
            ReadableFakeEntity::class
        ]);

        Horus::setFileHandler($this->fileHandler);

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

    protected function getDateTimeUtil(): DateTimeUtil
    {
        return new DateTimeUtil();
    }

}