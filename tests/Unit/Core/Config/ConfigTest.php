<?php

namespace Tests\Unit\Core\Config;

use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\FeatureName;
use AppTank\Horus\Core\Config\WebSocketConfig;
use Tests\TestCase;

class ConfigTest extends TestCase
{
    function testWebsocketConfigurationCanBeInjected(): void
    {
        $websocketConfig = new WebSocketConfig(
            default: 'custom',
            connectionName: 'custom',
            driver: 'custom',
            key: 'custom-key',
            secret: 'custom-secret',
            appId: 'custom-app',
            host: 'localhost',
            port: 9000,
            scheme: 'https',
            useTLS: true,
            clientOptions: ['timeout' => 5],
        );

        $config = new Config(websocketConfig: $websocketConfig);

        $this->assertSame($websocketConfig, $config->websocketConfig);
    }

    function testWebsocketConfigurationUsesTheExpectedBroadcastingFormat(): void
    {
        $this->assertSame([
            'default' => 'reverb',
            'connections' => [
                'reverb' => [
                    'driver' => 'reverb',
                    'key' => 'horus-app-key',
                    'secret' => 'horus-app-secret',
                    'app_id' => 'horus-app',
                    'options' => [
                        'host' => '127.0.0.1',
                        'port' => 8080,
                        'scheme' => 'http',
                        'useTLS' => false,
                    ],
                    'client_options' => [],
                ],
            ],
        ], (new WebSocketConfig())->toArray());
    }

    function testLegacyWebsocketConfigurationArrayIsNormalized(): void
    {
        $config = new Config(websocketConfig: [
            'default' => 'custom',
            'connections' => [
                'custom' => [
                    'driver' => 'custom',
                    'key' => 'key',
                ],
            ],
        ]);

        $this->assertInstanceOf(WebSocketConfig::class, $config->websocketConfig);
        $this->assertSame('custom', $config->websocketConfig->default);
        $this->assertSame('custom', $config->websocketConfig->connectionName);
        $this->assertSame('custom', $config->websocketConfig->driver);
        $this->assertSame('key', $config->websocketConfig->key);
    }

    function testWebsocketConfigurationIsPreservedWhenConfigIsSerialized(): void
    {
        $websocketConfig = new WebSocketConfig(
            key: 'serialized-key',
            port: 9090,
            useTLS: true,
        );

        $config = unserialize(serialize(new Config(websocketConfig: $websocketConfig)));

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals($websocketConfig, $config->websocketConfig);
    }

    function testWebsocketFeatureIsEnabledByDefault(): void
    {
        $config = new Config();

        $this->assertTrue($config->isFeatureEnabled(FeatureName::WEBSOCKET));
    }

    function testWebsocketFeatureCanBeDisabled(): void
    {
        $config = new Config(disabledFeatures: [FeatureName::WEBSOCKET]);

        $this->assertFalse($config->isFeatureEnabled(FeatureName::WEBSOCKET));
    }
}