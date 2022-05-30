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
        'tabs' => [
            'fields' => [
                'guest_order' => [
                    'label' => 'lang:igniter::system.settings.label_guest_order',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_order',
                    'type' => 'switch',
                    'on' => 'lang:igniter::admin.text_yes',
                    'off' => 'lang:igniter::admin.text_no',
                    'comment' => 'lang:igniter::system.settings.help_guest_order',
                ],
                'location_order' => [
                    'label' => 'lang:igniter::system.settings.label_location_order',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_order',
                    'type' => 'switch',
                    'default' => false,
                    'on' => 'lang:igniter::admin.text_yes',
                    'off' => 'lang:igniter::admin.text_no',
                    'comment' => 'lang:igniter::system.settings.help_location_order',
                ],
                'order_email' => [
                    'label' => 'lang:igniter::system.settings.label_order_email',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_order',
                    'type' => 'checkboxtoggle',
                    'options' => [
                        'customer' => 'lang:igniter::system.settings.text_to_customer',
                        'admin' => 'lang:igniter::system.settings.text_to_admin',
                        'location' => 'lang:igniter::system.settings.text_to_location',
                    ],
                    'comment' => 'lang:igniter::system.settings.help_order_email',
                ],
                'default_order_status' => [
                    'label' => 'lang:igniter::system.settings.label_default_order_status',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_order',
                    'type' => 'selectlist',
                    'mode' => 'radio',
                    'options' => [\Igniter\Admin\Models\Status::class, 'getDropdownOptionsForOrder'],
                    'comment' => 'lang:igniter::system.settings.help_default_order_status',
                ],
                'processing_order_status' => [
                    'label' => 'lang:igniter::system.settings.label_processing_order_status',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_order',
                    'type' => 'selectlist',
                    'options' => [\Igniter\Admin\Models\Status::class, 'getDropdownOptionsForOrder'],
                    'comment' => 'lang:igniter::system.settings.help_processing_order_status',
                ],
                'completed_order_status' => [
                    'label' => 'lang:igniter::system.settings.label_completed_order_status',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_order',
                    'type' => 'selectlist',
                    'options' => [\Igniter\Admin\Models\Status::class, 'getDropdownOptionsForOrder'],
                    'comment' => 'lang:igniter::system.settings.help_completed_order_status',
                ],
                'canceled_order_status' => [
                    'label' => 'lang:igniter::system.settings.label_canceled_order_status',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_order',
                    'type' => 'selectlist',
                    'mode' => 'radio',
                    'options' => [\Igniter\Admin\Models\Status::class, 'getDropdownOptionsForOrder'],
                    'comment' => 'lang:igniter::system.settings.help_canceled_order_status',
                ],

                'reservation_email' => [
                    'label' => 'lang:igniter::system.settings.label_reservation_email',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_reservation',
                    'type' => 'checkboxtoggle',
                    'options' => [
                        'customer' => 'lang:igniter::system.settings.text_to_customer',
                        'admin' => 'lang:igniter::system.settings.text_to_admin',
                        'location' => 'lang:igniter::system.settings.text_to_location',
                    ],
                    'comment' => 'lang:igniter::system.settings.help_reservation_email',
                ],
                'default_reservation_status' => [
                    'label' => 'lang:igniter::system.settings.label_default_reservation_status',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_reservation',
                    'type' => 'selectlist',
                    'mode' => 'radio',
                    'options' => [\Igniter\Admin\Models\Status::class, 'getDropdownOptionsForReservation'],
                    'comment' => 'lang:igniter::system.settings.help_default_reservation_status',
                ],
                'confirmed_reservation_status' => [
                    'label' => 'lang:igniter::system.settings.label_confirmed_reservation_status',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_reservation',
                    'type' => 'selectlist',
                    'mode' => 'radio',
                    'options' => [\Igniter\Admin\Models\Status::class, 'getDropdownOptionsForReservation'],
                    'comment' => 'lang:igniter::system.settings.help_confirmed_reservation_status',
                ],
                'canceled_reservation_status' => [
                    'label' => 'lang:igniter::system.settings.label_canceled_reservation_status',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_reservation',
                    'type' => 'selectlist',
                    'mode' => 'radio',
                    'options' => [\Igniter\Admin\Models\Status::class, 'getDropdownOptionsForReservation'],
                    'comment' => 'lang:igniter::system.settings.help_canceled_reservation_status',
                ],

                'invoice_prefix' => [
                    'label' => 'lang:igniter::system.settings.label_invoice_prefix',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_invoice',
                    'type' => 'text',
                    'span' => 'left',
                    'comment' => 'lang:igniter::system.settings.help_invoice_prefix',
                ],
                'invoice_logo' => [
                    'label' => 'lang:igniter::system.settings.label_invoice_logo',
                    'tab' => 'lang:igniter::system.settings.text_tab_title_invoice',
                    'type' => 'mediafinder',
                    'span' => 'right',
                    'mode' => 'inline',
                    'comment' => 'lang:igniter::system.settings.help_invoice_logo',
                ],
            ],
        ],
    ],
];
