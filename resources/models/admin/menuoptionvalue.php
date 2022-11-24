<?php

$config['form'] = [
    'fields' => [
        'option_value_id' => [
            'type' => 'hidden',
        ],
        'option_id' => [
            'label' => 'lang:igniter::admin.menu_options.label_option_id',
            'type' => 'hidden',
        ],
        'value' => [
            'label' => 'lang:igniter::admin.menu_options.label_option_value',
            'type' => 'text',
        ],
        'price' => [
            'label' => 'lang:igniter::admin.menu_options.label_option_price',
            'type' => 'currency',
        ],
        'stock_qty' => [
            'label' => 'lang:igniter::admin.menus.label_stock_qty',
            'type' => 'stockeditor',
            'span' => 'right',
        ],
        'ingredients' => [
            'label' => 'lang:igniter::admin.menus.label_ingredients',
            'type' => 'relation',
            'span' => 'right',
            'attributes' => [
                'data-number-displayed' => 1,
            ],
        ],
        'priority' => [
            'label' => 'lang:igniter::admin.menu_options.label_priority',
            'type' => 'hidden',
        ],
    ],
    'rules' => [
        ['option_id', 'lang:igniter::admin.menu_options.label_option_id', 'required|integer'],
        ['value', 'lang:igniter::admin.menu_options.label_option_value', 'required|min:2|max:128'],
        ['price', 'lang:igniter::admin.menu_options.label_option_price', 'required|numeric|min:0'],
        ['priority', 'lang:igniter::admin.menu_options.label_option_price', 'integer'],
        ['ingredients.*', 'lang:igniter::admin.menus.label_ingredients', 'integer'],
    ],
];

return $config;
