<?php

$config['list']['toolbar'] = [
    'buttons' => [
        'browse' => [
            'label' => 'lang:igniter::system.themes.button_browse',
            'class' => 'btn btn-primary',
            'href' => 'https://tastyigniter.com/marketplace/themes',
            'target' => '_blank',
        ],
        'check' => [
            'label' => 'lang:igniter::system.updates.button_check',
            'class' => 'btn btn-success',
            'href' => 'updates',
        ],
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-paint-brush',
        'attributes' => [
            'class' => 'btn btn-outline-default mr-2',
            'href' => 'themes/edit/{code}',
        ],
    ],
    'source' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-file',
        'attributes' => [
            'class' => 'btn btn-outline-default mr-2',
            'href' => 'themes/source/{code}',
        ],
    ],
    'default' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-star-o',
        'attributes' => [
            'class' => 'btn btn-outline-warning mr-2 bg-transparent',
            'title' => 'lang:igniter::system.themes.text_set_default',
            'data-request' => 'onSetDefault',
            'data-request-form' => '#list-form',
            'data-request-data' => 'code:\'{code}\'',
        ],
    ],
    'delete' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-trash-o',
        'attributes' => [
            'class' => 'btn btn-outline-danger',
            'href' => 'themes/delete/{code}',
        ],
    ],
    'name' => [
        'label' => 'lang:igniter::admin.label_name',
        'type' => 'text',
        'searchable' => true,
    ],
    'theme_id' => [
        'label' => 'lang:igniter::admin.column_id',
        'invisible' => true,
    ],
    'created_at' => [
        'label' => 'lang:igniter::admin.column_date_added',
        'invisible' => true,
        'type' => 'timesense',
    ],
    'updated_at' => [
        'label' => 'lang:igniter::admin.column_date_updated',
        'invisible' => true,
        'type' => 'timesense',
    ],
];

$config['form']['toolbar'] = [
    'buttons' => [
        'back' => [
            'label' => 'lang:igniter::admin.button_icon_back',
            'class' => 'btn btn-default ml-0',
            'href' => 'themes',
        ],
        'save' => [
            'label' => 'lang:igniter::admin.button_save',
            'class' => 'btn btn-primary',
            'data-request' => 'onSave',
            'data-progress-indicator' => 'igniter::admin.text_saving',
        ],
    ],
];

$config['form']['fields'] = [
    'name' => [
        'label' => 'lang:igniter::admin.label_name',
        'type' => 'text',
        'span' => 'left',
        'disabled' => true,
    ],
    'code' => [
        'label' => 'lang:igniter::system.themes.label_code',
        'type' => 'text',
        'span' => 'right',
        'disabled' => true,
    ],
    'template' => [
        'label' => 'lang:igniter::system.themes.label_template',
        'type' => 'templateeditor',
        'context' => ['source'],
    ],
];

$config['form']['tabs'] = [
    'cssClass' => 'theme-customizer',
    'fields' => [],
];

return $config;
