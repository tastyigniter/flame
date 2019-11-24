<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cart database settings
    |--------------------------------------------------------------------------
    |
    | Here you can set the model that the cart should use when
    | storing and restoring a cart.
    |
    */

    'model' => '\Igniter\Flame\Cart\Models\Cart',

    /*
    |--------------------------------------------------------------------------
    | Destroy the cart on user logout
    |--------------------------------------------------------------------------
    |
    | When this option is set to 'true' the cart will automatically
    | destroy all cart instances when the user logs out.
    |
    */

    'destroyOnLogout' => FALSE,

    'conditions' => [],

    'abandonedCart' => FALSE,
];