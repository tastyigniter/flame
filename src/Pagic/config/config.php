<?php
return [

    /*
     |--------------------------------------------------------------------------
     | Assetic Options
     |--------------------------------------------------------------------------
     |
     | Note: CacheBusting doesn't seem to work with seperate files yet.
     | It works fine when debug is false.
     |
     */

    'cacheBusting' => TRUE,

    'debug' => \Config::get('app.debug'),

    'cacheStore' => [
        'driver' => 'file',
        'path'   => storage_path('framework/cache/data'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Filter Manager
     |--------------------------------------------------------------------------
     |
     | A filter manager is also provided for organizing filters.
     |
     */
    'parser'     => function (FilterManager $fm) {
        // $fm->set('sass', new SassFilter('/path/to/parser/sass'));
        // $fm->set('yui_css', new Yui\CssCompressorFilter('/path/to/yuicompressor.jar'));
    },
];
