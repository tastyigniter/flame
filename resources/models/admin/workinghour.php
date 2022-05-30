<?php

return [
    'form' => [
        'fields' => [
            'type' => [
                'label' => 'lang:igniter::admin.locations.label_schedule_type',
                'type' => 'radiotoggle',
                'default' => 'daily',
                'options' => [
                    '24_7' => 'lang:igniter::admin.locations.text_24_7',
                    'daily' => 'lang:igniter::admin.locations.text_daily',
                    'timesheet' => 'lang:igniter::admin.locations.text_timesheet',
                    'flexible' => 'lang:igniter::admin.locations.text_flexible',
                ],
            ],
            'days' => [
                'label' => 'lang:igniter::admin.locations.label_schedule_days',
                'type' => 'checkboxtoggle',
                'options' => 'getWeekDaysOptions',
                'default' => [0, 1, 2, 3, 4, 5, 6],
                'trigger' => [
                    'action' => 'show',
                    'field' => 'type',
                    'condition' => 'value[daily]',
                ],
            ],
            'open' => [
                'label' => 'lang:igniter::admin.locations.label_schedule_open',
                'type' => 'datepicker',
                'default' => '12:00 AM',
                'mode' => 'time',
                'span' => 'left',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'type',
                    'condition' => 'value[daily]',
                ],
            ],
            'close' => [
                'label' => 'lang:igniter::admin.locations.label_schedule_close',
                'type' => 'datepicker',
                'default' => '11:59 PM',
                'mode' => 'time',
                'span' => 'right',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'type',
                    'condition' => 'value[daily]',
                ],
            ],
            'timesheet' => [
                'label' => 'lang:igniter::admin.locations.text_timesheet',
                'type' => 'partial',
                'path' => 'locations/timesheet',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'type',
                    'condition' => 'value[timesheet]',
                ],
            ],
            'flexible' => [
                'label' => 'lang:igniter::admin.locations.text_flexible',
                'type' => 'partial',
                'path' => 'locations/flexible_hours',
                'commentAbove' => 'lang:igniter::admin.locations.help_flexible_hours',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'type',
                    'condition' => 'value[flexible]',
                ],
            ],
        ],
    ],
];
