<?php

namespace AppTank\Horus\Core\Config;

/**
 * Configuration used by the broadcasting connection for Horus websocket events.
 */
final readonly class WebSocketConfig
{
    public function __construct(
        public string $default = 'reverb',
        public string $connectionName = 'reverb',
        public string $driver = 'reverb',
        public string $key = 'horus-app-key',
        public string $secret = 'horus-app-secret',
        public string $appId = 'horus-app',
        public string $host = '127.0.0.1',
        public int $port = 8080,
        public string $scheme = 'http',
        public bool $useTLS = false,
        public array $clientOptions = [],
    ) {
    }

    /**
     * Creates a typed configuration from an array or fallback configuration sources.
     *
     * @param array $config The broadcasting or horusync configuration.
     */
    public static function fromArray(array $config = []): self
    {
        if (empty($config) && class_exists(\Illuminate\Container\Container::class)) {
            $container = \Illuminate\Container\Container::getInstance();
            if ($container !== null && $container->bound('config')) {
                $configRepository = $container->make('config');
                $packageConfig = $configRepository->get('horusync.websocket') ?? $configRepository->get('horusync');
                if (is_array($packageConfig)) {
                    $config = $packageConfig;
                }
            }
        }

        if (isset($config['websocket']) && is_array($config['websocket'])) {
            $config = $config['websocket'];
        }

        $default = $config['default'] ?? (function_exists('env') ? env('HORUS_WEBSOCKET_DEFAULT', 'reverb') : 'reverb');
        $connections = is_array($config['connections'] ?? null) ? $config['connections'] : [];
        $connectionName = $config['connectionName']
            ?? $config['connection']
            ?? $config['connection_name']
            ?? (
                is_string($default) && isset($connections[$default])
                    ? $default
                    : (
                        !empty($connections)
                            ? (string) array_key_first($connections)
                            : (function_exists('env') ? env('HORUS_WEBSOCKET_CONNECTION', 'reverb') : 'reverb')
                    )
            );

        $connection = is_array($connections[$connectionName] ?? null)
            ? $connections[$connectionName]
            : $config;

        $options = is_array($connection['options'] ?? null)
            ? $connection['options']
            : (is_array($config['options'] ?? null) ? $config['options'] : []);

        $driver = $connection['driver']
            ?? $config['driver']
            ?? (function_exists('env') ? env('HORUS_WEBSOCKET_DRIVER', 'reverb') : 'reverb');

        $key = $connection['key']
            ?? $config['key']
            ?? (function_exists('env') ? env('HORUS_WEBSOCKET_KEY', 'horus-app-key') : 'horus-app-key');

        $secret = $connection['secret']
            ?? $config['secret']
            ?? (function_exists('env') ? env('HORUS_WEBSOCKET_SECRET', 'horus-app-secret') : 'horus-app-secret');

        $appId = $connection['app_id']
            ?? $connection['appId']
            ?? $config['app_id']
            ?? $config['appId']
            ?? (function_exists('env') ? env('HORUS_WEBSOCKET_APP_ID', 'horus-app') : 'horus-app');

        $host = $options['host']
            ?? $connection['host']
            ?? $config['host']
            ?? (function_exists('env') ? env('HORUS_WEBSOCKET_HOST', '127.0.0.1') : '127.0.0.1');

        $port = $options['port']
            ?? $connection['port']
            ?? $config['port']
            ?? (function_exists('env') ? (int) env('HORUS_WEBSOCKET_PORT', 8080) : 8080);

        $scheme = $options['scheme']
            ?? $connection['scheme']
            ?? $config['scheme']
            ?? (function_exists('env') ? env('HORUS_WEBSOCKET_SCHEME', 'http') : 'http');

        $useTLS = $options['useTLS']
            ?? $options['use_tls']
            ?? $connection['useTLS']
            ?? $connection['use_tls']
            ?? $config['useTLS']
            ?? $config['use_tls']
            ?? (function_exists('env') ? (bool) env('HORUS_WEBSOCKET_USE_TLS', false) : false);

        $clientOptions = $connection['client_options']
            ?? $connection['clientOptions']
            ?? $config['client_options']
            ?? $config['clientOptions']
            ?? [];

        return new self(
            default: is_string($default) ? $default : 'reverb',
            connectionName: is_string($connectionName) ? $connectionName : 'reverb',
            driver: is_string($driver) ? $driver : 'reverb',
            key: is_string($key) ? $key : 'horus-app-key',
            secret: is_string($secret) ? $secret : 'horus-app-secret',
            appId: is_string($appId) ? $appId : 'horus-app',
            host: is_string($host) ? $host : '127.0.0.1',
            port: is_numeric($port) ? (int) $port : 8080,
            scheme: is_string($scheme) ? $scheme : 'http',
            useTLS: (bool) $useTLS,
            clientOptions: is_array($clientOptions) ? $clientOptions : [],
        );
    }

    /**
     * Converts the typed configuration into Laravel's broadcasting format.
     */
    public function toArray(): array
    {
        return [
            'default' => $this->default,
            'connections' => [
                $this->connectionName => [
                    'driver' => $this->driver,
                    'key' => $this->key,
                    'secret' => $this->secret,
                    'app_id' => $this->appId,
                    'options' => [
                        'host' => $this->host,
                        'port' => $this->port,
                        'scheme' => $this->scheme,
                        'useTLS' => $this->useTLS,
                    ],
                    'client_options' => $this->clientOptions,
                ],
            ],
        ];
    }
}