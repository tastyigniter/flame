<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Default Provider name
    |---------------------------------------------------------------------------
    |
    | The `chain` provider is special, in that it will run all configured
    | providers in the sequence listed, should the previous provider fail.
    |
    */
    'default' => 'chain',

    /*
    |---------------------------------------------------------------------------
    | Providers
    |---------------------------------------------------------------------------
    |
    | Here you may specify any number of providers that should be used to
    | perform geocoding operations.
    |
    | You can explicitly call subsequently listed providers by
    | alias: `app('geocoder')->using('google')`.
    |
    */

    'providers' => [
        'google' => [
            'endpoints' => [
                'geocode' => 'https://maps.googleapis.com/maps/api/geocode/json?address=%s',
                'reverse' => 'https://maps.googleapis.com/maps/api/geocode/json?latlng=%F,%F',
            ],
            'locale' => 'en-GB',
            'region' => 'GB',
            'apiKey' => null
        ],
        'nominatim' => [
            'endpoints' => [
                'geocode' => 'https://nominatim.openstreetmap.org/search?q=%s&format=json&addressdetails=1&limit=%d',
                'reverse' => 'https://nominatim.openstreetmap.org/reverse?format=json&lat=%F&lon=%F&addressdetails=1&zoom=%d',
            ],
            'locale' => 'en-GB',
            'region' => 'GB',
        ],
    ],

    'cache' => [
        /*
        |-----------------------------------------------------------------------
        | Cache Store
        |-----------------------------------------------------------------------
        |
        | Specify the cache store to use for caching. The value "null" will use
        | the default cache store specified in /config/cache.php file.
        |
        | Default: null
        |
        */

        'store' => null,

        /*
        |-----------------------------------------------------------------------
        | Cache Duration
        |-----------------------------------------------------------------------
        |
        | Specify the cache duration in minutes. The default approximates a
        | "forever" cache, but there are certain issues with Laravel's forever
        | caching methods that prevent us from using them in this project.
        |
        | Default: 4320 (integer) 3 days
        |
        */

        'duration' => 4320,
    ],
];