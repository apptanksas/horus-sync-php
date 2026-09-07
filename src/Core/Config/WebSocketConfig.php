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
     * Creates a typed configuration from the legacy broadcasting array.
     *
     * @param array $config The broadcasting configuration.
     */
    public static function fromArray(array $config): self
    {
        $default = $config['default'] ?? 'reverb';
        $connections = is_array($config['connections'] ?? null) ? $config['connections'] : [];
        $connectionName = is_string($default) && isset($connections[$default])
            ? $default
            : (string) (array_key_first($connections) ?? 'reverb');
        $connection = is_array($connections[$connectionName] ?? null)
            ? $connections[$connectionName]
            : [];
        $options = is_array($connection['options'] ?? null)
            ? $connection['options']
            : [];

        return new self(
            default: is_string($default) ? $default : 'reverb',
            connectionName: $connectionName,
            driver: is_string($connection['driver'] ?? null) ? $connection['driver'] : 'reverb',
            key: is_string($connection['key'] ?? null) ? $connection['key'] : 'horus-app-key',
            secret: is_string($connection['secret'] ?? null) ? $connection['secret'] : 'horus-app-secret',
            appId: is_string($connection['app_id'] ?? null) ? $connection['app_id'] : 'horus-app',
            host: is_string($options['host'] ?? null) ? $options['host'] : '127.0.0.1',
            port: is_int($options['port'] ?? null) ? $options['port'] : 8080,
            scheme: is_string($options['scheme'] ?? null) ? $options['scheme'] : 'http',
            useTLS: is_bool($options['useTLS'] ?? null) ? $options['useTLS'] : false,
            clientOptions: is_array($connection['client_options'] ?? null) ? $connection['client_options'] : [],
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