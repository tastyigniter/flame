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
            'site_name' => [
                'label' => 'igniter::system.settings.label_site_name',
                'tab' => 'igniter::system.settings.text_tab_restaurant',
                'type' => 'text',
            ],
            'site_email' => [
                'label' => 'igniter::system.settings.label_site_email',
                'tab' => 'igniter::system.settings.text_tab_restaurant',
                'type' => 'text',
            ],
            'site_logo' => [
                'label' => 'igniter::system.settings.label_site_logo',
                'tab' => 'igniter::system.settings.text_tab_restaurant',
                'type' => 'mediafinder',
            ],
            'menus_page' => [
                'label' => 'igniter::system.settings.label_menus_page',
                'tab' => 'igniter::system.settings.text_tab_title_order',
                'type' => 'selectlist',
                'mode' => 'radio',
                'default' => 'local'.DIRECTORY_SEPARATOR.'menus',
                'comment' => 'igniter::system.settings.help_menus_page',
            ],
            'reservation_page' => [
                'label' => 'igniter::system.settings.label_reservation_page',
                'tab' => 'igniter::system.settings.text_tab_title_order',
                'type' => 'selectlist',
                'mode' => 'radio',
                'default' => 'reservation'.DIRECTORY_SEPARATOR.'reservation',
                'comment' => 'igniter::system.settings.help_reservation_page',
            ],
            'maps' => [
                'label' => 'igniter::system.settings.text_tab_title_maps',
                'tab' => 'igniter::system.settings.text_tab_restaurant',
                'type' => 'section',
            ],
            'distance_unit' => [
                'label' => 'igniter::system.settings.label_distance_unit',
                'tab' => 'igniter::system.settings.text_tab_restaurant',
                'type' => 'radiotoggle',
                'options' => [
                    'mi' => 'igniter::system.settings.text_miles',
                    'km' => 'igniter::system.settings.text_kilometers',
                ],
            ],
            'default_geocoder' => [
                'label' => 'igniter::system.settings.label_default_geocoder',
                'tab' => 'igniter::system.settings.text_tab_restaurant',
                'type' => 'radiotoggle',
                'default' => 'chain',
                'comment' => 'igniter::system.settings.help_default_geocoder',
                'options' => [
                    'nominatim' => 'igniter::system.settings.text_nominatim',
                    'google' => 'igniter::system.settings.text_google_geocoder',
                    'chain' => 'igniter::system.settings.text_chain_geocoder',
                ],
            ],
            'maps_api_key' => [
                'label' => 'igniter::system.settings.label_maps_api_key',
                'tab' => 'igniter::system.settings.text_tab_restaurant',
                'type' => 'text',
                'comment' => 'igniter::system.settings.help_maps_api_key',
                'trigger' => [
                    'action' => 'disable',
                    'field' => 'default_geocoder',
                    'condition' => 'value[nominatim]',
                ],
            ],
        ],
    ],
];
