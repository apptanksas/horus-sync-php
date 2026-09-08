<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horus WebSocket Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the WebSocket settings used by Horus synchronization.
    | These parameters define the credentials and connection options for the
    | WebSocket server and broadcasting client.
    |
    */

    'websocket' => [
        'default' => env('HORUS_WEBSOCKET_DEFAULT', 'reverb'),
        'connection' => env('HORUS_WEBSOCKET_CONNECTION', 'reverb'),
        'driver' => env('HORUS_WEBSOCKET_DRIVER', 'reverb'),
        'key' => env('HORUS_WEBSOCKET_KEY', 'horus-app-key'),
        'secret' => env('HORUS_WEBSOCKET_SECRET', 'horus-app-secret'),
        'app_id' => env('HORUS_WEBSOCKET_APP_ID', 'horus-app'),
        'host' => env('HORUS_WEBSOCKET_HOST', '127.0.0.1'),
        'port' => (int) env('HORUS_WEBSOCKET_PORT', 8080),
        'scheme' => env('HORUS_WEBSOCKET_SCHEME', 'http'),
        'use_tls' => (bool) env('HORUS_WEBSOCKET_USE_TLS', false),
        'options' => [
            'host' => env('HORUS_WEBSOCKET_HOST', '127.0.0.1'),
            'port' => (int) env('HORUS_WEBSOCKET_PORT', 8080),
            'scheme' => env('HORUS_WEBSOCKET_SCHEME', 'http'),
            'useTLS' => (bool) env('HORUS_WEBSOCKET_USE_TLS', false),
        ],
        'client_options' => [],
    ],

];
