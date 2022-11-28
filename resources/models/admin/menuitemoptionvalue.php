<?php

$config['form']['fields'] = [
    'menu_option_value_id' => [
        'type' => 'hidden',
    ],
    'priority' => [
        'type' => 'hidden',
    ],
    'option_value_id' => [
        'label' => 'lang:igniter::admin.menu_options.label_option_value',
        'type' => 'select',
    ],
    'new_price' => [
        'label' => 'lang:igniter::admin.menu_options.label_new_price',
        'type' => 'currency',
    ],
    'is_default' => [
        'label' => 'lang:igniter::admin.menu_options.label_option_default_value',
        'type' => 'checkbox',
        'options' => [],
    ],
];

return $config;
