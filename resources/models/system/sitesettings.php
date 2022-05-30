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
            'country_id' => [
                'label' => 'lang:igniter::system.settings.label_country',
                'tab' => 'lang:igniter::system.settings.text_tab_restaurant',
                'type' => 'select',
                'options' => ['Igniter\System\Models\Country', 'getDropdownOptions'],
            ],
            'language' => [
                'label' => 'lang:igniter::system.settings.text_tab_title_language',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'type' => 'section',
            ],
            'default_language' => [
                'label' => 'lang:igniter::system.settings.label_site_language',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'type' => 'select',
                'default' => 'en',
                'span' => 'left',
                'options' => ['Igniter\System\Models\Language', 'getDropdownOptions'],
                'placeholder' => 'lang:igniter::admin.text_please_select',
            ],
            'detect_language' => [
                'label' => 'lang:igniter::system.settings.label_detect_language',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'type' => 'switch',
                'default' => false,
                'comment' => 'lang:igniter::system.settings.help_detect_language',
            ],
            'currency' => [
                'label' => 'lang:igniter::system.settings.text_tab_title_currency',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'type' => 'section',
            ],
            'default_currency_code' => [
                'label' => 'lang:igniter::system.settings.label_site_currency',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'span' => 'left',
                'type' => 'select',
                'default' => 'GBP',
                'options' => ['Igniter\System\Models\Currencyl', 'getDropdownOptions'],
                'placeholder' => 'lang:igniter::admin.text_please_select',
                'comment' => 'lang:igniter::system.settings.help_site_currency',
            ],
            'currency_converter[api]' => [
                'label' => 'lang:igniter::system.settings.label_currency_converter',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'span' => 'right',
                'type' => 'select',
                'default' => 'openexchangerates',
                'options' => ['Igniter\System\Models\Currenciey', 'getConverterDropdownOptions'],
            ],
            'currency_converter[oer][apiKey]' => [
                'label' => 'lang:igniter::system.settings.label_currency_converter_oer_api_key',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'type' => 'text',
                'span' => 'left',
                'comment' => 'lang:igniter::system.settings.help_currency_converter_oer_api',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'currency_converter[api]',
                    'condition' => 'value[openexchangerates]',
                ],
            ],
            'currency_converter[fixerio][apiKey]' => [
                'label' => 'lang:igniter::system.settings.label_currency_converter_fixer_api_key',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'type' => 'text',
                'span' => 'left',
                'comment' => 'lang:igniter::system.settings.help_currency_converter_fixer_api',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'currency_converter[api]',
                    'condition' => 'value[fixerio]',
                ],
            ],
            'currency_converter[refreshInterval]' => [
                'label' => 'lang:igniter::system.settings.label_currency_refresh_interval',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'span' => 'right',
                'type' => 'select',
                'default' => '24',
                'options' => [
                    '1' => 'lang:igniter::system.settings.text_1_hour',
                    '3' => 'lang:igniter::system.settings.text_3_hours',
                    '6' => 'lang:igniter::system.settings.text_6_hours',
                    '12' => 'lang:igniter::system.settings.text_12_hours',
                    '24' => 'lang:igniter::system.settings.text_24_hours',
                ],
            ],
            'date' => [
                'label' => 'lang:igniter::system.settings.text_tab_title_date_time',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'type' => 'section',
            ],
            'timezone' => [
                'label' => 'lang:igniter::system.settings.label_timezone',
                'tab' => 'lang:igniter::system.settings.text_tab_site',
                'type' => 'select',
                'options' => 'listTimezones',
                'comment' => 'lang:igniter::system.settings.help_timezone',
                'placeholder' => 'lang:igniter::admin.text_please_select',
            ],
        ],
    ],
];
