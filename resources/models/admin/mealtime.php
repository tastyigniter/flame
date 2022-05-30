<?php
$config['list']['filter'] = [
    'scopes' => [
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
            'type' => 'switch',
            'conditions' => 'mealtime_status = :filtered',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:igniter::admin.button_new',
            'class' => 'btn btn-primary',
            'href' => 'mealtimes/create',
        ],
    ],
];

$config['list']['bulkActions'] = [
    'status' => [
        'label' => 'lang:igniter::admin.list.actions.label_status',
        'type' => 'dropdown',
        'class' => 'btn btn-light',
        'statusColumn' => 'mealtime_status',
        'menuItems' => [
            'enable' => [
                'label' => 'lang:igniter::admin.list.actions.label_enable',
                'type' => 'button',
                'class' => 'dropdown-item',
            ],
            'disable' => [
                'label' => 'lang:igniter::admin.list.actions.label_disable',
                'type' => 'button',
                'class' => 'dropdown-item text-danger',
            ],
        ],
    ],
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
            'href' => 'mealtimes/edit/{mealtime_id}',
        ],
    ],
    'mealtime_name' => [
        'label' => 'lang:igniter::admin.label_name',
        'type' => 'text',
    ],
    'start_time' => [
        'label' => 'lang:igniter::admin.mealtimes.column_start_time',
        'type' => 'time',
    ],
    'end_time' => [
        'label' => 'lang:igniter::admin.mealtimes.column_end_time',
        'type' => 'time',
    ],
    'mealtime_status' => [
        'label' => 'lang:igniter::admin.label_status',
        'type' => 'switch',
    ],
    'mealtime_id' => [
        'label' => 'lang:igniter::admin.column_id',
        'invisible' => true,
    ],
    'locations' => [
        'label' => 'lang:igniter::admin.column_location',
        'type' => 'text',
        'relation' => 'locations',
        'select' => 'location_name',
        'locationAware' => true,
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
            'class' => 'btn btn-outline-secondary',
            'href' => 'mealtimes',
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
    'mealtime_name' => [
        'label' => 'lang:igniter::admin.mealtimes.label_mealtime_name',
        'type' => 'text',
        'span' => 'left',
    ],
    'locations' => [
        'label' => 'lang:igniter::admin.label_location',
        'type' => 'relation',
        'span' => 'right',
        'valueFrom' => 'locations',
        'nameFrom' => 'location_name',
    ],
    'start_time' => [
        'label' => 'lang:igniter::admin.mealtimes.label_start_time',
        'type' => 'datepicker',
        'mode' => 'time',
        'span' => 'left',
    ],
    'end_time' => [
        'label' => 'lang:igniter::admin.mealtimes.label_end_time',
        'type' => 'datepicker',
        'mode' => 'time',
        'span' => 'right',
    ],
    'mealtime_status' => [
        'label' => 'lang:igniter::admin.label_status',
        'type' => 'switch',
        'default' => true,
        'span' => 'left',
    ],
];

return $config;
