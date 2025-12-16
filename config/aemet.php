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
        'stations' => env('AEMET_CACHE_TTL_24H', 86400),
        'recent_data' => env('AEMET_CACHE_TTL_1H', 3600),
        'historical_data' => env('AEMET_CACHE_TTL_30D', 604800),
    ],

];
