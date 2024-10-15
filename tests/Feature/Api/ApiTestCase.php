<?php

namespace Tests\Feature\Api;


use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Provider\HorusServiceProvider;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use Faker\Generator;
use Mockery\Mock;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Tests\_Stubs\AdjacentFakeWritableEntity;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\ReadableFakeEntity;
use Tests\_Stubs\ParentFakeWritableEntity;

class ApiTestCase extends TestCase
{
    use WithWorkbench;

    protected Generator $faker;
    private IFileHandler|Mock $fileHandler;

    protected array $middlewares = [];

    protected function setUp(): void
    {
        $this->fileHandler = \Mockery::mock(IFileHandler::class);

        Horus::initialize([
            ParentFakeWritableEntity::class => [
                ChildFakeWritableEntity::class,
                AdjacentFakeWritableEntity::class
            ],
            ReadableFakeEntity::class
        ]);

        Horus::setMiddlewares($this->middlewares);
        Horus::setFileHandler($this->fileHandler);

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