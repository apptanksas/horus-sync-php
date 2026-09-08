<?php

namespace Tests\Feature;

use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\WebSocketConfig;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Console\HorusStartWebSocketCommand;
use AppTank\Horus\Illuminate\Provider\HorusServiceProvider;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Tests\TestCase;

class HorusWebSocketConfigurationTest extends TestCase
{
    function testConfigPublishesHorusyncConfigFile(): void
    {
        (new HorusServiceProvider($this->app))->boot();

        $paths = HorusServiceProvider::pathsToPublish(HorusServiceProvider::class, 'horusync-config');

        $this->assertNotEmpty($paths);
        $expectedSource = realpath(__DIR__ . '/../../config/horusync.php');
        $this->assertArrayHasKey($expectedSource, $paths);
        $this->assertSame(config_path('horusync.php'), $paths[$expectedSource]);
    }

    function testServiceProviderUsesPublishedHorusyncConfigWhenNoExplicitConfigIsSet(): void
    {
        $this->app['config']->set('horusync.websocket', [
            'default' => 'reverb',
            'connection' => 'reverb',
            'driver' => 'reverb',
            'key' => 'published-key',
            'secret' => 'published-secret',
            'app_id' => 'published-app',
            'options' => [
                'host' => '127.0.0.1',
                'port' => 8888,
                'scheme' => 'http',
                'useTLS' => false,
            ],
            'client_options' => [],
        ]);

        Horus::initialize([]);
        (new HorusServiceProvider($this->app))->register();

        $wsConfig = Horus::getInstance()->getConfig()->websocketConfig;
        $this->assertSame('published-key', $wsConfig->key);
        $this->assertSame('published-secret', $wsConfig->secret);
        $this->assertSame('published-app', $wsConfig->appId);
        $this->assertSame(8888, $wsConfig->port);

        $reverbApps = $this->app['config']->get('reverb.apps.apps');
        $horusApp = collect($reverbApps)->firstWhere('key', 'published-key');
        $this->assertNotNull($horusApp);
        $this->assertSame('published-app', $horusApp['app_id']);
        $this->assertSame(8888, $horusApp['options']['port']);
    }

    function testExplicitConfigOverridesHorusyncConfig(): void
    {
        $this->app['config']->set('horusync.websocket', [
            'key' => 'from-horusync-file',
            'secret' => 'from-horusync-file-secret',
            'app_id' => 'from-horusync-file-app',
            'port' => 8888,
        ]);

        $customConfig = new Config(
            websocketConfig: new WebSocketConfig(
                key: 'explicit-key',
                secret: 'explicit-secret',
                appId: 'explicit-app',
                host: '127.0.0.1',
                port: 9999
            )
        );
        Horus::getInstance()->setConfig($customConfig);

        (new HorusServiceProvider($this->app))->register();

        $wsConfig = Horus::getInstance()->getConfig()->websocketConfig;
        $this->assertSame('explicit-key', $wsConfig->key);
        $this->assertSame('explicit-secret', $wsConfig->secret);
        $this->assertSame('explicit-app', $wsConfig->appId);
        $this->assertSame(9999, $wsConfig->port);
    }

    function testServiceProviderInjectsReverbAppConfiguration(): void
    {
        $customConfig = new Config(
            websocketConfig: new WebSocketConfig(
                key: 'my-custom-key',
                secret: 'my-custom-secret',
                appId: 'my-custom-app',
                host: '127.0.0.1',
                port: 8090,
                scheme: 'http',
                useTLS: false
            )
        );
        Horus::getInstance()->setConfig($customConfig);

        (new HorusServiceProvider($this->app))->register();

        $reverbApps = $this->app['config']->get('reverb.apps.apps');
        $this->assertIsArray($reverbApps);

        $horusApp = collect($reverbApps)->firstWhere('key', 'my-custom-key');
        $this->assertNotNull($horusApp);
        $this->assertSame('my-custom-app', $horusApp['app_id']);
        $this->assertSame('my-custom-secret', $horusApp['secret']);
        $this->assertSame(8090, $horusApp['options']['port']);
        $this->assertSame('127.0.0.1', $this->app['config']->get('reverb.servers.reverb.host'));
        $this->assertSame(8090, $this->app['config']->get('reverb.servers.reverb.port'));
    }

    function testUnconfiguredReverbAppsWithNullValuesAreFilteredOutAndPruningWorks(): void
    {
        $this->app['config']->set('reverb.apps.apps', [
            [
                'key' => null,
                'secret' => null,
                'app_id' => null,
                'options' => [
                    'host' => null,
                    'port' => 443,
                    'scheme' => 'https',
                    'useTLS' => true,
                ],
                'allowed_origins' => ['*'],
                'ping_interval' => 60,
                'activity_timeout' => 30,
                'max_message_size' => 10_000,
            ],
        ]);

        $customConfig = new Config(
            websocketConfig: new WebSocketConfig(
                key: 'valid-horus-key',
                secret: 'valid-horus-secret',
                appId: 'valid-horus-app',
                host: '127.0.0.1',
                port: 8080
            )
        );
        Horus::getInstance()->setConfig($customConfig);

        (new HorusServiceProvider($this->app))->register();

        $reverbApps = $this->app['config']->get('reverb.apps.apps');
        $this->assertCount(1, $reverbApps);
        $this->assertSame('valid-horus-key', $reverbApps[0]['key']);
        $this->assertSame('valid-horus-app', $reverbApps[0]['app_id']);

        $command = $this->app->make(HorusStartWebSocketCommand::class);
        $command->getRoutes();

        /** @var ApplicationProvider $appProvider */
        $appProvider = $this->app->make(ApplicationProvider::class);
        $applications = $appProvider->all();

        $this->assertCount(1, $applications);
        $this->assertSame('valid-horus-app', $applications->first()->id());

        \Laravel\Reverb\Jobs\PruneStaleConnections::dispatch();
        $this->assertTrue(true);
    }
}
