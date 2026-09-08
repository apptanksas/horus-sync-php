<?php

namespace Tests\Feature;

use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\WebSocketConfig;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Provider\HorusServiceProvider;
use Tests\TestCase;

class HorusWebSocketConfigurationTest extends TestCase
{
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
}
