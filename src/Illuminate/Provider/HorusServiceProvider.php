<?php

namespace AppTank\Horus\Illuminate\Provider;

use AppTank\Horus\Client\HorusQueueActionClient;
use AppTank\Horus\Client\IHorusQueueActionClient;
use AppTank\Horus\Core\Bus\IEventBus;
use AppTank\Horus\Core\Bus\IJobDispatcher;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Repository\CacheRepository;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Core\Repository\MigrationSchemaRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use AppTank\Horus\Core\Transaction\ITransactionHandler;
use AppTank\Horus\Core\Util\IDateTimeUtil;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Bus\EventBus;
use AppTank\Horus\Illuminate\Bus\JobDispatcher;
use AppTank\Horus\Illuminate\Console\CreateEntitySynchronizableCommand;
use AppTank\Horus\Illuminate\Console\PruneFilesUploadedCommand;
use AppTank\Horus\Illuminate\Transaction\EloquentTransactionHandler;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use AppTank\Horus\Repository\DefaultCacheRepository;
use AppTank\Horus\Repository\EloquentEntityAccessValidatorRepository;
use AppTank\Horus\Repository\EloquentEntityRepository;
use AppTank\Horus\Repository\EloquentFileUploadedRepository;
use AppTank\Horus\Repository\EloquentQueueActionRepository;
use AppTank\Horus\Repository\EloquentSyncJobRepository;
use AppTank\Horus\Repository\StaticMigrationSchemaRepository;
use Carbon\Laravel\ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * @internal Horus Service Provider
 */
class HorusServiceProvider extends ServiceProvider
{
    /**
     * Bootstraps the application services.
     *
     * Registers commands, loads migrations, and sets up routes.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
        $this->app->afterResolving(function () {
            $this->registerRoutes();
        });
    }

    /**
     * Register the application services.
     *
     * Binds interfaces to their implementations in the service container.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->app->singleton(MigrationSchemaRepository::class, function () {
            return new StaticMigrationSchemaRepository();
        });

        $this->app->singleton(IEventBus::class, function () {
            return new EventBus();
        });

        $this->app->singleton(IJobDispatcher::class, function () {
            return new JobDispatcher();
        });

        $this->app->bind(EntityMapper::class, function () {
            return Horus::getInstance()->getEntityMapper();
        });

        $this->app->singleton(IDateTimeUtil::class, function () {
            return new DateTimeUtil();
        });

        $this->app->singleton(QueueActionRepository::class, function () {
            return new EloquentQueueActionRepository(
                $this->app->make(IDateTimeUtil::class),
                Horus::getInstance()->getConnectionName()
            );
        });

        $this->app->singleton(ITransactionHandler::class, function () {
            return new EloquentTransactionHandler(Horus::getInstance()->getConnectionName());
        });

        $this->app->bind(EntityRepository::class, function () {

            $horusInstance = Horus::getInstance();

            return new EloquentEntityRepository(
                $this->app->make(EntityMapper::class),
                $this->app->make(CacheRepository::class),
                $this->app->make(IDateTimeUtil::class),
                $horusInstance->getConfig(),
                $horusInstance->getConnectionName()
            );
        });

        $this->app->singleton(EntityAccessValidatorRepository::class, function () {
            return new EloquentEntityAccessValidatorRepository(
                $this->app->make(EntityMapper::class),
                Horus::getInstance()->getConfig(),
                $this->app->make(EntityRepository::class)
            );
        });

        $this->app->singleton(FileUploadedRepository::class, function () {
            return new EloquentFileUploadedRepository();
        });

        $this->app->singleton(SyncJobRepository::class, function () {
            return new EloquentSyncJobRepository();
        });

        $this->app->bind(IHorusQueueActionClient::class, function () {
            return new HorusQueueActionClient(
                $this->app->make(ITransactionHandler::class),
                $this->app->make(QueueActionRepository::class),
                $this->app->make(EntityRepository::class),
                Horus::getInstance()->getConfig()
            );
        });

        $this->app->singleton(CacheRepository::class, function () {
            return new DefaultCacheRepository();
        });


    }

    /**
     * Registers the application routes.
     *
     * Sets up route groups and loads the routes file.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => "horus/v1",
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../../../routes/api_v1.php');
        });
    }

    /**
     * Registers the application commands.
     *
     * Adds commands to the Artisan CLI if running in console mode.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateEntitySynchronizableCommand::class,
                PruneFilesUploadedCommand::class
            ]);
        }
    }
}
