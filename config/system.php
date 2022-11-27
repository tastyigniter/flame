<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TastyIgniter Carté Key
    |--------------------------------------------------------------------------
    |
    | The license key for the corresponding domain from your TastyIgniter account.
    | A Carte key is required to add and update item from the TastyIgniter Marketplace.
    |
    | https://tastyigniter.com/support/articles/carte-key
    |
    */

    'carteKey' => env('IGNITER_CARTE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Site Location Mode.
    |--------------------------------------------------------------------------
    |
    | Set whether to enable support for single or multiple restaurant locations.
    |
    | Supported: "single", "multiple"
    |
    */

    'locationMode' => env('IGNITER_LOCATION_MODE', 'multiple'),

    /*
    |--------------------------------------------------------------------------
    | Specifies the default themes.
    |--------------------------------------------------------------------------
    |
    | This parameter value can be overridden from the admin settings.
    |
    */

    'defaultTheme' => 'demo',

    /*
    |--------------------------------------------------------------------------
    | Themes location
    |--------------------------------------------------------------------------
    |
    | Specifies the relative theme path used for generating themes assets.
    |
    */

    'themesDir' => '/themes',

    /*
    |--------------------------------------------------------------------------
    | Public extensions path
    |--------------------------------------------------------------------------
    |
    | Specifies the public extensions absolute path.
    |
    */

    //'extensionsPath' => base_path('extensions'),

    /*
    |--------------------------------------------------------------------------
    | Public themes path
    |--------------------------------------------------------------------------
    |
    | Specifies the public themes absolute path.
    |
    */

    //'themesPath' => base_path('themes'),

    /*
    |--------------------------------------------------------------------------
    | Send the Powered-By Header
    |--------------------------------------------------------------------------
    |
    | Websites like builtwith.com use the X-Powered-By header to determine
    | what technologies are used on a particular site. By default, we'll
    | send this header, but you are absolutely allowed to disable it.
    |
    */

    'sendPoweredByHeader' => true,

    /*
    |--------------------------------------------------------------------------
    | Time to live for parsed Template Pages.
    |--------------------------------------------------------------------------
    |
    | Specifies the number of minutes the Template object cache lives. After the interval
    | is expired item are re-cached. Note that items are re-cached automatically when
    | the corresponding template file is modified.
    |
    */

    'parsedTemplateCacheTTL' => null,

    'parsedTemplateCachePath' => storage_path('igniter/cache'),

    /*
    |--------------------------------------------------------------------------
    | Assets storage
    |--------------------------------------------------------------------------
    |
    | Specifies the configuration for resource storage, such as media and
    | uploaded files. These resources are used:
    |
    | media   - generated by the media manager.
    | attachment   - generated by attaching media items to models.
    |
    | For each resource you can specify:
    |
    | disk   - filesystem disk, as specified in filesystems.php config.
    | folder - a folder prefix for storing all generated files inside.
    | path   - the public path relative to the application base URL,
    |          or you can specify a full URL path.
    */

    'assets' => [

        'media' => [
            'disk' => 'media',
            'folder' => 'uploads',
            'path' => '/media/uploads',
            'max_upload_size' => 1500,
            'enable_uploads' => true,
            'enable_new_folder' => true,
            'enable_rename' => true,
            'enable_move' => true,
            'enable_copy' => true,
            'enable_delete' => true,
        ],

        'attachment' => [
            'disk' => 'media',
            'folder' => 'attachments',
            'path' => '/media/attachments',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Linking policy
    |--------------------------------------------------------------------------
    |
    | Controls how URL links are generated.
    |
    | detect   - detect hostname and use the current schema
    | secure   - detect hostname and force HTTPS schema
    | insecure - detect hostname and force HTTP schema
    | force    - force hostname and schema using app.url config value
    |
    */

    'urlPolicy' => 'force',

    /*
    |--------------------------------------------------------------------------
    | Default permission mask
    |--------------------------------------------------------------------------
    |
    | Specifies a default file and folder permission for newly created objects.
    |
    */

    'filePermissions' => '644',
    'folderPermissions' => '755',

    /*
    |--------------------------------------------------------------------------
    | Cross Site Request Forgery (CSRF) Protection
    |--------------------------------------------------------------------------
    |
    | If the CSRF protection is enabled, all "postback" requests are checked
    | for a valid security token.
    |
    */

    'enableCsrfProtection' => true,
];
