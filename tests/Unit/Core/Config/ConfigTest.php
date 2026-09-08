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

    function testWebsocketConfigCanBeParsedFromHorusyncFormatArray(): void
    {
        $wsConfig = WebSocketConfig::fromArray([
            'websocket' => [
                'default' => 'reverb',
                'connection' => 'reverb',
                'driver' => 'reverb',
                'key' => 'horusync-key',
                'secret' => 'horusync-secret',
                'app_id' => 'horusync-app',
                'host' => '10.0.0.1',
                'port' => 8081,
                'scheme' => 'https',
                'use_tls' => true,
                'client_options' => ['verify' => false],
            ],
        ]);

        $this->assertSame('horusync-key', $wsConfig->key);
        $this->assertSame('horusync-secret', $wsConfig->secret);
        $this->assertSame('horusync-app', $wsConfig->appId);
        $this->assertSame('10.0.0.1', $wsConfig->host);
        $this->assertSame(8081, $wsConfig->port);
        $this->assertSame('https', $wsConfig->scheme);
        $this->assertTrue($wsConfig->useTLS);
        $this->assertSame(['verify' => false], $wsConfig->clientOptions);
    }

    function testWebsocketConfigCanBeParsedFromFlatArray(): void
    {
        $wsConfig = WebSocketConfig::fromArray([
            'key' => 'flat-key',
            'secret' => 'flat-secret',
            'app_id' => 'flat-app',
            'host' => '127.0.0.2',
            'port' => 9091,
            'scheme' => 'http',
            'useTLS' => false,
        ]);

        $this->assertSame('flat-key', $wsConfig->key);
        $this->assertSame('flat-secret', $wsConfig->secret);
        $this->assertSame('flat-app', $wsConfig->appId);
        $this->assertSame('127.0.0.2', $wsConfig->host);
        $this->assertSame(9091, $wsConfig->port);
    }

    function testWebsocketConfigReadsHorusEnvironmentVariablesWhenAvailable(): void
    {
        $this->app['config']->set('horusync', null);

        putenv('HORUS_WEBSOCKET_KEY=env-key');
        putenv('HORUS_WEBSOCKET_SECRET=env-secret');
        putenv('HORUS_WEBSOCKET_APP_ID=env-app');
        putenv('HORUS_WEBSOCKET_HOST=192.168.1.50');
        putenv('HORUS_WEBSOCKET_PORT=8282');
        putenv('HORUS_WEBSOCKET_SCHEME=https');
        putenv('HORUS_WEBSOCKET_USE_TLS=true');
        putenv('HORUS_WEBSOCKET_DRIVER=reverb');
        putenv('HORUS_WEBSOCKET_DEFAULT=reverb');
        putenv('HORUS_WEBSOCKET_CONNECTION=reverb');

        try {
            $wsConfig = WebSocketConfig::fromArray([]);

            $this->assertSame('env-key', $wsConfig->key);
            $this->assertSame('env-secret', $wsConfig->secret);
            $this->assertSame('env-app', $wsConfig->appId);
            $this->assertSame('192.168.1.50', $wsConfig->host);
            $this->assertSame(8282, $wsConfig->port);
            $this->assertSame('https', $wsConfig->scheme);
            $this->assertTrue($wsConfig->useTLS);
        } finally {
            putenv('HORUS_WEBSOCKET_KEY');
            putenv('HORUS_WEBSOCKET_SECRET');
            putenv('HORUS_WEBSOCKET_APP_ID');
            putenv('HORUS_WEBSOCKET_HOST');
            putenv('HORUS_WEBSOCKET_PORT');
            putenv('HORUS_WEBSOCKET_SCHEME');
            putenv('HORUS_WEBSOCKET_USE_TLS');
            putenv('HORUS_WEBSOCKET_DRIVER');
            putenv('HORUS_WEBSOCKET_DEFAULT');
            putenv('HORUS_WEBSOCKET_CONNECTION');
        }
    }

    function testConfigTracksExplicitAndDefaultWebsocketConfiguration(): void
    {
        $defaultConfig = new Config();
        $this->assertFalse($defaultConfig->hasExplicitWebSocketConfig);

        $explicitConfig = new Config(websocketConfig: new WebSocketConfig());
        $this->assertTrue($explicitConfig->hasExplicitWebSocketConfig);
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