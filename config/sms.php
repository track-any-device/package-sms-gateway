<?php

return [
    'gateway' => [
        /*
         * Base URL of the SMS gateway device (e.g. http://192.168.1.100:8080).
         */
        'url' => env('SMS_GATEWAY_URL'),

        /*
         * API key sent as X-API-Key on every authenticated request.
         */
        'api_key' => env('SMS_GATEWAY_API_KEY'),

        /*
         * HTTP timeout in seconds.
         */
        'timeout' => env('SMS_GATEWAY_TIMEOUT', 15),
    ],
];
