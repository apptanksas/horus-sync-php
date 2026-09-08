<?php

namespace Tests\Unit\Illuminate\Console;

use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\WebSocketConfig;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Console\HorusStartWebSocketCommand;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Protocols\Pusher\Contracts\ChannelConnectionManager;
use Laravel\Reverb\Protocols\Pusher\Contracts\ChannelManager;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\PusherController;
use Laravel\Reverb\Protocols\Pusher\Server as PusherServer;
use Laravel\Reverb\Servers\Reverb\Contracts\PubSubIncomingMessageHandler;
use Laravel\Reverb\Servers\Reverb\Http\Server as HttpServer;
use React\EventLoop\Loop;
use React\Socket\ServerInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Tests\TestCase;

class HorusStartWebSocketCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Loop::set(new \React\EventLoop\StreamSelectLoop());
        gc_enable();
        parent::tearDown();
    }

    function testCommandRoutesIncludeBothHorusAndAppPrefixes(): void
    {
        $command = $this->app->make(HorusStartWebSocketCommand::class);
        $routes = $command->getRoutes();

        $this->assertNotNull($routes->get('horus_sockets'));
        $this->assertNotNull($routes->get('sockets'));
        $this->assertNotNull($routes->get('events'));
        $this->assertNotNull($routes->get('events_batch'));
        $this->assertNotNull($routes->get('connections'));
        $this->assertNotNull($routes->get('channels'));
        $this->assertNotNull($routes->get('channel'));
        $this->assertNotNull($routes->get('channel_users'));
        $this->assertNotNull($routes->get('users_terminate'));
        $this->assertNotNull($routes->get('health_check'));

        $matcher = new UrlMatcher($routes, new RequestContext());

        $horusMatch = $matcher->match('/horus/test-key');
        $this->assertSame('horus_sockets', $horusMatch['_route']);
        $this->assertSame('test-key', $horusMatch['appKey']);

        $appMatch = $matcher->match('/app/test-key');
        $this->assertSame('sockets', $appMatch['_route']);
        $this->assertSame('test-key', $appMatch['appKey']);
    }

    function testCommandEnsuresReverbBindings(): void
    {
        $command = $this->app->make(HorusStartWebSocketCommand::class);
        $command->getRoutes();

        $this->assertTrue($this->app->bound(ChannelManager::class));
        $this->assertTrue($this->app->bound(ChannelConnectionManager::class));
        $this->assertTrue($this->app->bound(PubSubIncomingMessageHandler::class));
    }

    function testCreateServerReturnsHttpServerInstance(): void
    {
        Horus::getInstance()->setConfig(new Config(
            websocketConfig: new WebSocketConfig(
                key: 'custom-key',
                host: '127.0.0.1',
                port: 8089
            )
        ));

        $socket = \Mockery::mock(ServerInterface::class);
        $socket->shouldReceive('on')->once();

        $command = $this->app->make(HorusStartWebSocketCommand::class);
        $server = $command->createServer('127.0.0.1', 8089, null, $socket);

        $this->assertInstanceOf(HttpServer::class, $server);
    }
}
