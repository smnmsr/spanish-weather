<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AEMET API Key
    |--------------------------------------------------------------------------
    |
    | Your AEMET OpenData API key. Register at https://opendata.aemet.es/
    | to obtain your API key.
    |
    */

    'api_key' => env('AEMET_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | AEMET Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the AEMET OpenData API.
    |
    */

    'base_url' => env('AEMET_BASE_URL', 'https://opendata.aemet.es/opendata'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | Time in seconds to cache AEMET API responses. Historical data is cached
    | longer than recent data. Default is 1 hour (3600 seconds).
    |
    */

    'cache_ttl' => [
        'stations' => env('AEMET_CACHE_TTL', 86400), // 24 hours for station list
        'recent_data' => env('AEMET_CACHE_TTL', 3600), // 1 hour for recent data
        'historical_data' => env('AEMET_CACHE_TTL', 604800), // 7 days for historical data
    ],

];
