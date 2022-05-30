<?php
$config['list']['filter'] = [
    'search' => [
        'prompt' => 'lang:igniter::admin.reservations.text_filter_search',
        'mode' => 'all',
    ],
    'scopes' => [
        'assignee' => [
            'label' => 'lang:igniter::admin.reservations.text_filter_assignee',
            'type' => 'select',
            'scope' => 'filterAssignedTo',
            'options' => [
                1 => 'lang:igniter::admin.statuses.text_unassigned',
                2 => 'lang:igniter::admin.statuses.text_assigned_to_self',
                3 => 'lang:igniter::admin.statuses.text_assigned_to_others',
            ],
        ],
        'location' => [
            'label' => 'lang:igniter::admin.text_filter_location',
            'type' => 'selectlist',
            'scope' => 'whereHasLocation',
            'modelClass' => \Igniter\Admin\Models\Location::class,
            'nameFrom' => 'location_name',
            'locationAware' => true,
        ],
        'status' => [
            'label' => 'lang:igniter::admin.text_filter_status',
            'type' => 'selectlist',
            'conditions' => 'status_id IN(:filtered)',
            'modelClass' => \Igniter\Admin\Models\Status::class,
            'options' => 'getDropdownOptionsForReservation',
        ],
        'date' => [
            'label' => 'lang:igniter::admin.text_filter_date',
            'type' => 'daterange',
            'conditions' => 'reserve_date >= CAST(:filtered_start AS DATE) AND reserve_date <= CAST(:filtered_end AS DATE)',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:igniter::admin.button_new',
            'class' => 'btn btn-primary',
            'href' => 'reservations/create',
        ],
        'calendar' => [
            'label' => 'lang:igniter::admin.reservations.text_switch_to_calendar',
            'class' => 'btn btn-default',
            'href' => 'reservations/calendar',
            'context' => 'index',
        ],
    ],
];

$config['list']['bulkActions'] = [
    'delete' => [
        'label' => 'lang:igniter::admin.button_delete',
        'class' => 'btn btn-light text-danger',
        'data-request-confirm' => 'lang:igniter::admin.alert_warning_confirm',
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-pencil',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'reservations/edit/{reservation_id}',
        ],
    ],
    'reservation_id' => [
        'label' => 'lang:igniter::admin.column_id',
    ],
    'location_name' => [
        'label' => 'lang:igniter::admin.reservations.column_location',
        'relation' => 'location',
        'select' => 'location_name',
        'searchable' => true,
        'locationAware' => true,
    ],
    'full_name' => [
        'label' => 'lang:igniter::admin.label_name',
        'select' => "concat(first_name, ' ', last_name)",
        'searchable' => true,
    ],
    'guest_num' => [
        'label' => 'lang:igniter::admin.reservations.column_guest',
        'type' => 'number',
        'searchable' => true,
    ],
    'table_name' => [
        'label' => 'lang:igniter::admin.reservations.column_table',
        'type' => 'text',
        'relation' => 'tables',
        'select' => 'table_name',
        'searchable' => true,
    ],
    'status_name' => [
        'label' => 'lang:igniter::admin.label_status',
        'relation' => 'status',
        'select' => 'status_name',
        'type' => 'partial',
        'path' => 'statuses/status_column',
        'searchable' => true,
    ],
    'assignee_name' => [
        'label' => 'lang:igniter::admin.reservations.column_staff',
        'type' => 'text',
        'relation' => 'assignee',
        'select' => 'name',
    ],
    'reserve_time' => [
        'label' => 'lang:igniter::admin.reservations.column_time',
        'type' => 'time',
    ],
    'reserve_date' => [
        'label' => 'lang:igniter::admin.reservations.column_date',
        'type' => 'date',
    ],
];

$config['calendar']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:igniter::admin.button_new',
            'class' => 'btn btn-primary',
            'href' => 'reservations/create',
        ],
        'list' => [
            'label' => 'lang:igniter::admin.text_switch_to_list',
            'class' => 'btn btn-default',
            'href' => 'reservations',
            'context' => 'calendar',
        ],
    ],
];

$config['form']['toolbar'] = [
    'buttons' => [
        'back' => [
            'label' => 'lang:igniter::admin.button_icon_back',
            'class' => 'btn btn-outline-secondary',
            'href' => 'reservations',
        ],
        'save' => [
            'label' => 'lang:igniter::admin.button_save',
            'context' => ['create', 'edit'],
            'partial' => 'form/toolbar_save_button',
            'class' => 'btn btn-primary',
            'data-request' => 'onSave',
            'data-progress-indicator' => 'igniter::admin.text_saving',
        ],
        'delete' => [
            'label' => 'lang:igniter::admin.button_icon_delete',
            'class' => 'btn btn-danger',
            'data-request' => 'onDelete',
            'data-request-data' => "_method:'DELETE'",
            'data-request-confirm' => 'lang:igniter::admin.alert_warning_confirm',
            'data-progress-indicator' => 'igniter::admin.text_deleting',
            'context' => ['edit'],
        ],
    ],
];

$config['form']['fields'] = [
    '_info' => [
        'type' => 'partial',
        'disabled' => true,
        'path' => 'reservations/info',
        'span' => 'left',
        'context' => ['edit', 'preview'],
    ],
    'status_id' => [
        'type' => 'statuseditor',
        'span' => 'right',
        'context' => ['edit', 'preview'],
        'form' => 'reservationstatus',
        'request' => \Igniter\Admin\Requests\ReservationStatus::class,
    ],
];

$config['form']['tabs'] = [
    'defaultTab' => 'lang:igniter::admin.reservations.text_tab_general',
    'fields' => [
        'first_name' => [
            'label' => 'lang:igniter::admin.reservations.label_first_name',
            'type' => 'text',
            'span' => 'left',
        ],
        'last_name' => [
            'label' => 'lang:igniter::admin.reservations.label_last_name',
            'type' => 'text',
            'span' => 'right',
        ],
        'email' => [
            'label' => 'lang:igniter::admin.label_email',
            'type' => 'text',
            'span' => 'left',
        ],
        'telephone' => [
            'label' => 'lang:igniter::admin.reservations.label_customer_telephone',
            'type' => 'text',
            'span' => 'right',
        ],
        'reserve_date' => [
            'label' => 'lang:igniter::admin.reservations.label_reservation_date',
            'type' => 'datepicker',
            'mode' => 'date',
            'span' => 'left',
            'cssClass' => 'flex-width',
        ],
        'reserve_time' => [
            'label' => 'lang:igniter::admin.reservations.label_reservation_time',
            'type' => 'datepicker',
            'mode' => 'time',
            'span' => 'left',
            'cssClass' => 'flex-width',
        ],
        'location_id' => [
            'label' => 'lang:igniter::admin.reservations.text_tab_restaurant',
            'type' => 'relation',
            'relationFrom' => 'location',
            'nameFrom' => 'location_name',
            'span' => 'right',
            'placeholder' => 'lang:igniter::admin.text_please_select',
        ],
        'guest_num' => [
            'label' => 'lang:igniter::admin.reservations.label_guest',
            'type' => 'number',
            'span' => 'left',
            'cssClass' => 'flex-width',
        ],
        'tables' => [
            'label' => 'lang:igniter::admin.reservations.label_table_name',
            'type' => 'relation',
            'relationFrom' => 'tables',
            'nameFrom' => 'table_name',
            'span' => 'left',
            'cssClass' => 'flex-width',
        ],
        'duration' => [
            'label' => 'lang:igniter::admin.reservations.label_reservation_duration',
            'type' => 'number',
            'span' => 'right',
            'comment' => 'lang:igniter::admin.reservations.help_reservation_duration',
        ],
        'notify' => [
            'label' => 'lang:igniter::admin.reservations.label_send_confirmation',
            'type' => 'switch',
            'span' => 'left',
            'default' => 1,
        ],
        'comment' => [
            'label' => 'lang:igniter::admin.statuses.label_comment',
            'type' => 'textarea',
        ],
        'created_at' => [
            'label' => 'lang:igniter::admin.reservations.label_date_added',
            'type' => 'datepicker',
            'mode' => 'date',
            'disabled' => true,
            'span' => 'left',
            'context' => ['edit', 'preview'],
        ],
        'ip_address' => [
            'label' => 'lang:igniter::admin.reservations.label_ip_address',
            'type' => 'text',
            'span' => 'right',
            'disabled' => true,
            'context' => ['edit', 'preview'],
        ],
        'updated_at' => [
            'label' => 'lang:igniter::admin.reservations.label_date_modified',
            'type' => 'datepicker',
            'mode' => 'date',
            'disabled' => true,
            'span' => 'left',
            'context' => ['edit', 'preview'],
        ],
        'user_agent' => [
            'label' => 'lang:igniter::admin.reservations.label_user_agent',
            'type' => 'text',
            'span' => 'right',
            'disabled' => true,
            'context' => ['edit', 'preview'],
        ],
        'status_history' => [
            'tab' => 'lang:igniter::admin.reservations.text_status_history',
            'type' => 'datatable',
            'context' => ['edit', 'preview'],
            'useAjax' => true,
            'defaultSort' => ['status_history_id', 'desc'],
            'columns' => [
                'date_added_since' => [
                    'title' => 'lang:igniter::admin.reservations.column_date_time',
                ],
                'status_name' => [
                    'title' => 'lang:igniter::admin.label_status',
                ],
                'comment' => [
                    'title' => 'lang:igniter::admin.reservations.column_comment',
                ],
                'notified' => [
                    'title' => 'lang:igniter::admin.reservations.column_notify',
                ],
                'staff_name' => [
                    'title' => 'lang:igniter::admin.reservations.column_staff',
                ],
            ],
        ],
    ],
];

return $config;
