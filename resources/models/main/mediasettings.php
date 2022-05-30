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
            'image_manager[max_size]' => [
                'label' => 'lang:igniter::system.settings.label_media_max_size',
                'type' => 'number',
                'default' => 300,
                'comment' => 'lang:igniter::system.settings.help_media_max_size',
            ],
            'image_manager[uploads]' => [
                'label' => 'lang:igniter::system.settings.label_media_uploads',
                'type' => 'switch',
                'default' => true,
                'span' => 'left',
                'comment' => 'lang:igniter::system.settings.help_media_upload',
            ],
            'image_manager[new_folder]' => [
                'label' => 'lang:igniter::system.settings.label_media_new_folder',
                'type' => 'switch',
                'default' => true,
                'span' => 'right',
                'comment' => 'lang:igniter::system.settings.help_media_new_folder',
            ],
            'image_manager[copy]' => [
                'label' => 'lang:igniter::system.settings.label_media_copy',
                'type' => 'switch',
                'default' => true,
                'span' => 'left',
                'comment' => 'lang:igniter::system.settings.help_media_copy',
            ],
            'image_manager[move]' => [
                'label' => 'lang:igniter::system.settings.label_media_move',
                'type' => 'switch',
                'default' => true,
                'span' => 'right',
                'comment' => 'lang:igniter::system.settings.help_media_move',
            ],
            'image_manager[rename]' => [
                'label' => 'lang:igniter::system.settings.label_media_rename',
                'type' => 'switch',
                'default' => true,
                'span' => 'left',
                'comment' => 'lang:igniter::system.settings.help_media_rename',
            ],
            'image_manager[delete]' => [
                'label' => 'lang:igniter::system.settings.label_media_delete',
                'type' => 'switch',
                'default' => true,
                'span' => 'right',
                'comment' => 'lang:igniter::system.settings.help_media_delete',
            ],
        ],
    ],
];
