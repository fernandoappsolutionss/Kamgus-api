<?php
return [
    "yappy" => [
        'driver' => [
            'YAPPY_ID_DEL_COMERCIO' => env('YAPPY_ID_DEL_COMERCIO'),
            'YAPPY_CLAVE_SECRETA' => env('YAPPY_CLAVE_SECRETA'),
            'YAPPY_MODO_DE_PRUEBAS' => env('YAPPY_MODO_DE_PRUEBAS'),
            'YAPPY_PLUGIN_VERSION' => env('YAPPY_PLUGIN_VERSION'),
        ],
        'invited' => [
            'YAPPY_ID_DEL_COMERCIO' => env('INVITED_YAPPY_ID_DEL_COMERCIO'),
            'YAPPY_CLAVE_SECRETA' => env('INVITED_YAPPY_CLAVE_SECRETA'),
            'YAPPY_MODO_DE_PRUEBAS' => env('INVITED_YAPPY_MODO_DE_PRUEBAS'),
            'YAPPY_PLUGIN_VERSION' => env('INVITED_YAPPY_PLUGIN_VERSION'),
        ],
    ],
];