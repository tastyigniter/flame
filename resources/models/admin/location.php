<?php

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'lang:igniter::admin.locations.text_filter_search',
        'mode' => 'all',
    ],
    'scopes' => [
        'status' => [
            'label' => 'lang:igniter::admin.text_filter_status',
            'type' => 'switch',
            'conditions' => 'location_status = :filtered',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:igniter::admin.button_new',
            'class' => 'btn btn-primary',
            'href' => 'locations/create',
        ],
    ],
];

$config['list']['bulkActions'] = [
    'status' => [
        'label' => 'lang:igniter::admin.list.actions.label_status',
        'type' => 'dropdown',
        'class' => 'btn btn-light',
        'statusColumn' => 'location_status',
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
            'href' => 'locations/edit/{location_id}',
        ],
    ],
    'default' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-star-o',
        'attributes' => [
            'class' => 'btn btn-outline-warning bg-transparent',
            'data-request' => 'onSetDefault',
            'data-request-data' => 'default:{location_id}',
        ],
    ],
    'location_name' => [
        'label' => 'lang:igniter::admin.label_name',
        'type' => 'text',
        'searchable' => true,
    ],
    'location_city' => [
        'label' => 'lang:igniter::admin.locations.column_city',
        'type' => 'text',
        'searchable' => true,
    ],
    'location_state' => [
        'label' => 'lang:igniter::admin.locations.column_state',
        'type' => 'text',
        'searchable' => true,
    ],
    'location_postcode' => [
        'label' => 'lang:igniter::admin.locations.column_postcode',
        'type' => 'text',
        'searchable' => true,
    ],
    'location_telephone' => [
        'label' => 'lang:igniter::admin.locations.column_telephone',
        'type' => 'text',
        'searchable' => true,
    ],
    'location_status' => [
        'label' => 'lang:igniter::admin.label_status',
        'type' => 'switch',
    ],
    'location_id' => [
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
            'class' => 'btn btn-outline-secondary',
            'href' => 'locations',
        ],
        'save' => [
            'label' => 'lang:igniter::admin.button_save',
            'context' => ['create', 'edit', 'settings'],
            'partial' => 'form/toolbar_save_button',
            'saveActions' => ['continue', 'close'],
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

$config['form']['tabs'] = [
    'defaultTab' => 'lang:igniter::admin.locations.text_tab_general',
    'fields' => [
        'location_name' => [
            'label' => 'lang:igniter::admin.label_name',
            'type' => 'text',
            'span' => 'left',
        ],
        'permalink_slug' => [
            'label' => 'lang:igniter::admin.locations.label_permalink_slug',
            'type' => 'permalink',
            'span' => 'right',
            'comment' => 'lang:igniter::admin.help_permalink',
        ],
        'location_email' => [
            'label' => 'lang:igniter::admin.label_email',
            'type' => 'text',
            'span' => 'left',
        ],
        'location_telephone' => [
            'label' => 'lang:igniter::admin.locations.label_telephone',
            'type' => 'text',
            'span' => 'right',
        ],
        'location_address_1' => [
            'label' => 'lang:igniter::admin.locations.label_address_1',
            'type' => 'text',
            'span' => 'left',
        ],
        'location_address_2' => [
            'label' => 'lang:igniter::admin.locations.label_address_2',
            'type' => 'text',
            'span' => 'right',
        ],
        'location_city' => [
            'label' => 'lang:igniter::admin.locations.label_city',
            'type' => 'text',
            'span' => 'left',
        ],
        'location_state' => [
            'label' => 'lang:igniter::admin.locations.label_state',
            'type' => 'text',
            'span' => 'right',
        ],
        'location_postcode' => [
            'label' => 'lang:igniter::admin.locations.label_postcode',
            'type' => 'text',
            'span' => 'left',
        ],
        'location_country_id' => [
            'label' => 'lang:igniter::admin.locations.label_country',
            'type' => 'relation',
            'relationFrom' => 'country',
            'nameFrom' => 'country_name',
            'default' => setting('country_id'),
            'span' => 'right',
        ],
        'thumb' => [
            'label' => 'lang:igniter::admin.locations.label_image',
            'type' => 'mediafinder',
            'span' => 'left',
            'mode' => 'inline',
            'useAttachment' => true,
            'comment' => 'lang:igniter::admin.locations.help_image',
        ],
        'options[auto_lat_lng]' => [
            'label' => 'lang:igniter::admin.locations.label_auto_lat_lng',
            'type' => 'switch',
            'default' => true,
            'onText' => 'lang:igniter::admin.text_yes',
            'offText' => 'lang:igniter::admin.text_no',
            'span' => 'right',
            'cssClass' => 'flex-width',
        ],
        'location_status' => [
            'label' => 'lang:igniter::admin.label_status',
            'type' => 'switch',
            'default' => 1,
            'span' => 'right',
            'cssClass' => 'flex-width',
        ],
        'location_lat' => [
            'label' => 'lang:igniter::admin.locations.label_latitude',
            'type' => 'text',
            'span' => 'left',
            'trigger' => [
                'action' => 'hide',
                'field' => 'options[auto_lat_lng]',
                'condition' => 'checked',
            ],
        ],
        'location_lng' => [
            'label' => 'lang:igniter::admin.locations.label_longitude',
            'type' => 'text',
            'span' => 'right',
            'trigger' => [
                'action' => 'hide',
                'field' => 'options[auto_lat_lng]',
                'condition' => 'checked',
            ],
        ],
        'description' => [
            'label' => 'lang:igniter::admin.label_description',
            'type' => 'richeditor',
            'size' => 'small',
        ],

        '_working_hours' => [
            'tab' => 'lang:igniter::admin.locations.text_tab_schedules',
            'type' => 'scheduleeditor',
            'context' => ['edit'],
            'form' => 'workinghour',
            'request' => \Igniter\Admin\Requests\WorkingHour::class,
        ],

        'delivery_areas' => [
            'tab' => 'lang:igniter::admin.locations.text_tab_delivery',
            'label' => 'lang:igniter::admin.locations.text_delivery_area',
            'type' => 'maparea',
            'context' => ['edit'],
            'form' => 'locationarea',
            'request' => \Igniter\Admin\Requests\LocationArea::class,
            'commentAbove' => 'lang:igniter::admin.locations.help_delivery_areas',
        ],

        'options[gallery][title]' => [
            'label' => 'lang:igniter::admin.locations.label_gallery_title',
            'tab' => 'lang:igniter::admin.locations.text_tab_gallery',
            'type' => 'text',
        ],
        'options[gallery][description]' => [
            'label' => 'lang:igniter::admin.label_description',
            'tab' => 'lang:igniter::admin.locations.text_tab_gallery',
            'type' => 'textarea',
        ],
        'gallery' => [
            'label' => 'lang:igniter::admin.locations.label_gallery_add_image',
            'tab' => 'lang:igniter::admin.locations.text_tab_gallery',
            'type' => 'mediafinder',
            'isMulti' => true,
            'useAttachment' => true,
        ],
    ],
];

return $config;
