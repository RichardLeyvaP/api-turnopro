<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],  // Permite solicitudes desde cualquier origen

    'allowed_origins_patterns' => ['^https?:\/\/([a-z0-9-]+\.)*simplifies\.cl$'],  // Patrón para permitir subdominios

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

