<?php

namespace AppTank\Horus\Illuminate\Provider;

use AppTank\Horus\Core\Repository\MigrationSchemaRepository;
use AppTank\Horus\Illuminate\Console\CreateEntitySynchronizableCommand;
use AppTank\Horus\Repository\StaticMigrationSchemaRepository;
use Carbon\Laravel\ServiceProvider;
use Illuminate\Support\Facades\Route;

class HorusServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerCommands();
    }

    public function register()
    {
        parent::register();

        $this->app->singleton(MigrationSchemaRepository::class, function () {
            return new StaticMigrationSchemaRepository();
        });
    }

    protected function registerRoutes(): void
    {
        Route::group([
            'as' => 'horus.',
            'prefix' => "sync",
            'namespace' => 'AppTank\Horus\Illuminate\Http\Controller',
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