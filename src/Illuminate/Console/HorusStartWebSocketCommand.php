<?php

namespace AppTank\Horus\Illuminate\Console;

use AppTank\Horus\Horus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Laravel\Reverb\Application;
use Laravel\Reverb\ApplicationManager;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Contracts\Logger;
use Laravel\Reverb\Jobs\PingInactiveConnections;
use Laravel\Reverb\Jobs\PruneStaleConnections;
use Laravel\Reverb\Loggers\CliLogger;
use Laravel\Reverb\Loggers\NullLogger;
use Laravel\Reverb\Protocols\Pusher\Contracts\ChannelConnectionManager;
use Laravel\Reverb\Protocols\Pusher\Contracts\ChannelManager;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\ChannelController;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\ChannelsController;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\ChannelUsersController;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\ConnectionsController;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\EventsBatchController;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\EventsController;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\HealthCheckController;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\PusherController;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\UsersTerminateController;
use Laravel\Reverb\Protocols\Pusher\Managers\ArrayChannelConnectionManager;
use Laravel\Reverb\Protocols\Pusher\Managers\ArrayChannelManager;
use Laravel\Reverb\Protocols\Pusher\PusherPubSubIncomingMessageHandler;
use Laravel\Reverb\Protocols\Pusher\Server as PusherServer;
use Laravel\Reverb\Servers\Reverb\Contracts\PubSubIncomingMessageHandler;
use Laravel\Reverb\Servers\Reverb\Http\Route;
use Laravel\Reverb\Servers\Reverb\Http\Router;
use Laravel\Reverb\Servers\Reverb\Http\Server as HttpServer;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;
use React\Socket\SocketServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal Class HorusStartWebSocketCommand
 *
 * Command responsible for starting the independent Horus WebSocket server.
 */
#[AsCommand(name: 'horus:websocket')]
class HorusStartWebSocketCommand extends Command implements SignalableCommandInterface
{
    const string COMMAND_NAME = 'horus:websocket';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horus:websocket
                {--host= : The IP address the server should bind to}
                {--port= : The port the server should listen on}
                {--debug : Indicates whether debug messages should be displayed in the terminal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the independent Horus WebSocket server';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($this->option('debug')) {
            $this->getContainer()->instance(Logger::class, new CliLogger($this->output));
        }

        $wsConfig = Horus::getInstance()->getConfig()->websocketConfig;

        $host = $this->option('host') ?: $wsConfig->host;
        $port = $this->option('port') ?: (string) $wsConfig->port;

        $loop = Loop::get();

        $server = $this->createServer($host, $port, $loop);

        $this->ensureStaleConnectionsAreCleaned($loop);
        $this->ensureRestartCommandIsRespected($server, $loop, $host, $port);

        $this->components->info("Starting Horus WebSocket server on {$host}:{$port}");
        $this->components->twoColumnDetail('WebSocket URI', "ws://{$host}:{$port}/horus/{$wsConfig->key}");

        $server->start();
    }

    /**
     * Build the RouteCollection for Horus WebSocket server.
     */
    public function getRoutes(): RouteCollection
    {
        $this->ensureReverbBindings();

        $routes = new RouteCollection();
        $pusherController = new PusherController(
            $this->getContainer()->make(PusherServer::class),
            $this->getContainer()->make(ApplicationProvider::class)
        );

        $routes->add('horus_sockets', Route::get('/horus/{appKey}', $pusherController));
        $routes->add('sockets', Route::get('/app/{appKey}', $pusherController));
        $routes->add('events', Route::post('/apps/{appId}/events', new EventsController));
        $routes->add('events_batch', Route::post('/apps/{appId}/batch_events', new EventsBatchController));
        $routes->add('connections', Route::get('/apps/{appId}/connections', new ConnectionsController));
        $routes->add('channels', Route::get('/apps/{appId}/channels', new ChannelsController));
        $routes->add('channel', Route::get('/apps/{appId}/channels/{channel}', new ChannelController));
        $routes->add('channel_users', Route::get('/apps/{appId}/channels/{channel}/users', new ChannelUsersController));
        $routes->add('users_terminate', Route::post('/apps/{appId}/users/{userId}/terminate_connections', new UsersTerminateController));
        $routes->add('health_check', Route::get('/up', new HealthCheckController));

        return $routes;
    }

    /**
     * Create the HTTP WebSocket server instance.
     */
    public function createServer(
        string $host,
        string|int $port,
        ?LoopInterface $loop = null,
        ?ServerInterface $socket = null
    ): HttpServer {
        $loop = $loop ?: Loop::get();
        $router = new Router(new UrlMatcher($this->getRoutes(), new RequestContext()));
        $socket = $socket ?: new SocketServer("{$host}:{$port}", [], $loop);

        return new HttpServer($socket, $router, 10_000, $loop);
    }

    /**
     * Ensure default Reverb bindings are registered in the container.
     */
    protected function ensureReverbBindings(): void
    {
        $container = $this->getContainer();

        if ($container->bound('config')) {
            $config = $container->make('config');
            $reverbApps = $config->get('reverb.apps.apps', []);
            $filteredApps = array_values(array_filter($reverbApps, function ($app) {
                return is_array($app) && !empty($app['app_id']) && !empty($app['key']);
            }));
            if (count($filteredApps) !== count($reverbApps)) {
                $config->set('reverb.apps.apps', $filteredApps);
            }
        }

        $container->singletonIf(
            ApplicationManager::class,
            fn ($app) => new ApplicationManager($app)
        );

        $container->bindIf(
            ApplicationProvider::class,
            fn ($app) => $app->make(ApplicationManager::class)->driver()
        );

        $container->singletonIf(
            ChannelManager::class,
            fn () => new ArrayChannelManager
        );

        $container->bindIf(
            ChannelConnectionManager::class,
            fn () => new ArrayChannelConnectionManager
        );

        $container->singletonIf(
            Logger::class,
            fn () => new NullLogger
        );

        $container->singletonIf(
            PubSubIncomingMessageHandler::class,
            fn () => new PusherPubSubIncomingMessageHandler
        );
    }

    /**
     * Use the event loop to schedule periodic cleanup of connections.
     */
    protected function ensureStaleConnectionsAreCleaned(LoopInterface $loop): void
    {
        $loop->addPeriodicTimer(60, function () {
            PruneStaleConnections::dispatch();
            PingInactiveConnections::dispatch();
        });
    }

    /**
     * Check to see whether the restart signal has been sent.
     */
    protected function ensureRestartCommandIsRespected(HttpServer $server, LoopInterface $loop, string $host, string|int $port): void
    {
        $lastRestart = Cache::get('laravel:reverb:restart');

        $loop->addPeriodicTimer(5, function () use ($server, $host, $port, $lastRestart) {
            if ($lastRestart === Cache::get('laravel:reverb:restart')) {
                return;
            }

            $this->gracefullyDisconnect();

            $server->stop();

            $this->components->info("Stopping server on {$host}:{$port}");
        });
    }

    /**
     * Gracefully disconnect all connections.
     */
    protected function gracefullyDisconnect(): void
    {
        $container = $this->getContainer();

        if (!$container->bound(ApplicationProvider::class) || !$container->bound(ChannelManager::class)) {
            return;
        }

        $container->make(ApplicationProvider::class)
            ->all()
            ->each(function (Application $application) use ($container) {
                collect(
                    $container->make(ChannelManager::class)
                        ->for($application)
                        ->connections()
                )->each->disconnect();
            });
    }

    /**
     * Get the Laravel application container instance.
     */
    protected function getContainer()
    {
        return $this->laravel ?? app();
    }

    /**
     * Get the list of signals handled by the command.
     */
    public function getSubscribedSignals(): array
    {
        $isWindows = function_exists('windows_os') ? windows_os() : (DIRECTORY_SEPARATOR === '\\');

        if (! $isWindows && defined('SIGINT') && defined('SIGTERM') && defined('SIGTSTP')) {
            return [SIGINT, SIGTERM, SIGTSTP];
        }

        $this->handleSignalWindows();

        return [];
    }

    /**
     * Handle the signals sent to the server.
     */
    public function handleSignal(int $signal = 0, int|false $previousExitCode = 0): int|false
    {
        $this->components->info('Gracefully terminating connections.');

        $this->gracefullyDisconnect();

        return $previousExitCode;
    }

    /**
     * Handle the signals sent to the server on Windows.
     */
    public function handleSignalWindows(): void
    {
        if (function_exists('sapi_windows_set_ctrl_handler')) {
            sapi_windows_set_ctrl_handler(fn () => exit($this->handleSignal()));
        }
    }
}
