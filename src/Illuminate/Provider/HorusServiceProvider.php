<?php

namespace AppTank\Horus\Illuminate\Provider;

use AppTank\Horus\Core\Bus\IEventBus;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\MigrationSchemaRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\Transaction\ITransactionHandler;
use AppTank\Horus\Core\Util\IDateTimeUtil;
use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Bus\EventBus;
use AppTank\Horus\Illuminate\Console\CreateEntitySynchronizableCommand;
use AppTank\Horus\Illuminate\Transaction\EloquentTransactionHandler;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use AppTank\Horus\Repository\EloquentEntityAccessValidatorRepository;
use AppTank\Horus\Repository\EloquentEntityRepository;
use AppTank\Horus\Repository\EloquentQueueActionRepository;
use AppTank\Horus\Repository\StaticMigrationSchemaRepository;
use Carbon\Laravel\ServiceProvider;
use Illuminate\Support\Facades\Route;

class HorusServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->registerCommands();
        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
        $this->app->afterResolving(function () {
            $this->registerRoutes();
        });
    }

    public function register()
    {
        parent::register();

        $this->app->singleton(MigrationSchemaRepository::class, function () {
            return new StaticMigrationSchemaRepository();
        });

        $this->app->singleton(IEventBus::class, function () {
            return new EventBus();
        });

        $this->app->singleton(EntityMapper::class, function () {
            return HorusContainer::getInstance()->getEntityMapper();
        });

        $this->app->singleton(IDateTimeUtil::class, function () {
            return new DateTimeUtil();
        });

        $this->app->singleton(QueueActionRepository::class, function () {
            return new EloquentQueueActionRepository(
                $this->app->make(IDateTimeUtil::class),
                HorusContainer::getInstance()->getConnectionName()
            );
        });

        $this->app->singleton(ITransactionHandler::class, function () {
            return new EloquentTransactionHandler(HorusContainer::getInstance()->getConnectionName());
        });

        $this->app->singleton(EntityRepository::class, function () {
            return new EloquentEntityRepository(
                $this->app->make(EntityMapper::class),
                $this->app->make(IDateTimeUtil::class),
                HorusContainer::getInstance()->getConnectionName()
            );
        });

        $this->app->singleton(EntityAccessValidatorRepository::class, function () {
            return new EloquentEntityAccessValidatorRepository(
                $this->app->make(EntityMapper::class),
                HorusContainer::getInstance()->getConfig()
            );
        });


    }

    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => "sync",
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../../../routes/api.php');
        });
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateEntitySynchronizableCommand::class
            ]);
        }
    }
}