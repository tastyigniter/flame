<?php

$config['list']['columns'] = [
    'created_at' => [
        'label' => 'lang:igniter::admin.column_date_added',
        'type' => 'text',
        'searchable' => true,
    ],
    'message' => [
        'label' => 'lang:igniter::system.activities.column_message',
        'type' => 'text',
        'searchable' => true,
    ],
    'status_for_name' => [
        'label' => 'lang:igniter::admin.label_type',
        'type' => 'text',
        'searchable' => true,
    ],
    'notify_customer' => [
        'label' => 'lang:igniter::system.activities.column_notify',
        'type' => 'switch',
    ],
    'activity_id' => [
        'label' => 'lang:igniter::admin.column_id',
        'invisible' => true,
    ],

];

return $config;
