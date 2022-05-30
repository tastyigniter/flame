<?php

return [
    'form' => [
        'toolbar' => [
            'buttons' => [
                'back' => [
                    'label' => 'lang:igniter::admin.button_icon_back',
                    'class' => 'btn btn-outline-secondary',
                    'href' => 'settings',
                ],
                'save' => [
                    'label' => 'lang:igniter::admin.button_save',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onSave',
                    'data-progress-indicator' => 'igniter::admin.text_saving',
                ],
            ],
        ],
        'fields' => [
            'tax_mode' => [
                'label' => 'lang:igniter::system.settings.label_tax_mode',
                'type' => 'switch',
                'default' => false,
                'comment' => 'lang:igniter::system.settings.help_tax_mode',
            ],
            'tax_percentage' => [
                'label' => 'lang:igniter::system.settings.label_tax_percentage',
                'type' => 'number',
                'default' => 0,
                'comment' => 'lang:igniter::system.settings.help_tax_percentage',
            ],
            'tax_menu_price' => [
                'label' => 'lang:igniter::system.settings.label_tax_menu_price',
                'type' => 'select',
                'options' => [
                    'lang:igniter::system.settings.text_menu_price_include_tax',
                    'lang:igniter::system.settings.text_apply_tax_on_menu_price',
                ],
                'comment' => 'lang:igniter::system.settings.help_tax_menu_price',
            ],
            'tax_delivery_charge' => [
                'label' => 'lang:igniter::system.settings.label_tax_delivery_charge',
                'type' => 'switch',
                'on' => 'lang:igniter::admin.text_yes',
                'off' => 'lang:igniter::admin.text_no',
                'comment' => 'lang:igniter::system.settings.help_tax_delivery_charge',
            ],
        ],
    ],
];
