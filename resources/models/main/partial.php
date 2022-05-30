<?php

return [
    'form' => [
        'tabs' => [
            'fields' => [
                'markup' => [
                    'tab' => 'lang:igniter::system.themes.text_tab_markup',
                    'type' => 'codeeditor',
                    'mode' => 'application/x-httpd-php',
                ],
                'settings[description]' => [
                    'tab' => 'lang:igniter::system.themes.text_tab_meta',
                    'label' => 'lang:igniter::admin.label_description',
                    'type' => 'textarea',
                ],
            ],
        ],
        'rules' => [
            'markup' => ['string'],
            'settings.description' => ['max:255'],
        ],
        'validationAttributes' => [
            'markup' => lang('igniter::system.themes.text_tab_markup'),
            'settings.description' => lang('igniter::admin.label_description'),
        ],
    ],
];
