<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

//         PUSHER_APP_ID=123456
// PUSHER_APP_KEY=GoofNBCH
// PUSHER_APP_SECRET=stronfdff

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                // 'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'host' => '127.0.0.1',
                // 'port' => env('PUSHER_PORT', 443),
                'port' => 6001,
                // 'scheme' => env('PUSHER_SCHEME', 'https'),
                'scheme' => 'http',
                'encrypted' => true,
                // este no estaba comentado lo comente yo rlp 'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                'verify' => env('GUZZLE_SSL_VERIFY', true), // Esta opción controla si se realiza la verificación del certificado SSL
                'cert' => env('GUZZLE_CERT', 'C:\Users\Richard\Documents\GitHub\Simplifies\storage\app\public\licenc\cacert.pem'), // Esta opción especifica la ruta al archivo cacert.pem
            
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
