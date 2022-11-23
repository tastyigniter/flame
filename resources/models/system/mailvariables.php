<?php

return [
    'igniter::system.mail_variables.text_group_global' => [
        '{{ $site_name }}' => 'igniter::system.mail_variables.text_site_name',
        '{{ $site_logo }}' => 'igniter::system.mail_variables.text_site_logo',
    ],
    'igniter::system.mail_variables.text_group_staff_reset' => [
        '{{ $staff_name }}' => 'igniter::system.mail_variables.text_staff_name',
        '{{ $reset_link }}' => 'igniter::system.mail_variables.text_staff_reset_link',
    ],
    'igniter::system.mail_variables.text_group_registration' => [
        '{{ $first_name }}' => 'igniter::system.mail_variables.text_first_name',
        '{{ $last_name }}' => 'igniter::system.mail_variables.text_last_name',
        '{{ $account_login_link }}' => 'igniter::system.mail_variables.text_account_login_link',
    ],
    'igniter::system.mail_variables.text_group_reset' => [
        '{{ $first_name }}' => 'igniter::system.mail_variables.text_first_name',
        '{{ $last_name }}' => 'igniter::system.mail_variables.text_last_name',
        '{{ $reset_code }}' => 'igniter::system.mail_variables.text_reset_code',
        '{{ $reset_link }}' => 'igniter::system.mail_variables.text_reset_link',
        '{{ $account_login_link }}' => 'igniter::system.mail_variables.text_account_login_link',
    ],
    'igniter::system.mail_variables.text_group_order' => [
        '{{ $order_number }}' => 'igniter::system.mail_variables.text_order_number',
        '{{ $first_name }}' => 'igniter::system.mail_variables.text_first_name',
        '{{ $last_name }}' => 'igniter::system.mail_variables.text_last_name',
        '{{ $email }}' => 'igniter::system.mail_variables.text_email',
        '{{ $telephone }}' => 'igniter::system.mail_variables.text_telephone',
        '{{ $customer_name }}' => 'igniter::system.mail_variables.text_customer_name',
        '{{ $order_type }}' => 'igniter::system.mail_variables.text_order_type',
        '{{ $order_time }}' => 'igniter::system.mail_variables.text_order_time',
        '{{ $order_date }}' => 'igniter::system.mail_variables.text_order_date',
        '{{ $order_added }}' => 'igniter::system.mail_variables.text_order_added',
        '{{ $order_payment }}' => 'igniter::system.mail_variables.text_order_payment',
        '{{ $order_address }}' => 'igniter::system.mail_variables.text_order_address',
        '{{ $invoice_number }}' => 'igniter::system.mail_variables.text_invoice_number',
        '{{ $invoice_date }}' => 'igniter::system.mail_variables.text_invoice_date',
        '{{ $order_comment }}' => 'igniter::system.mail_variables.text_order_comment',
        '{{ $location_logo }}' => 'system::lang.mail_variables.text_location_logo',
        '{{ $location_name }}' => 'igniter::system.mail_variables.text_location_name',
        '{{ $location_email }}' => 'igniter::system.mail_variables.text_location_email',
        '{{ $location_telephone }}' => 'igniter::system.mail_variables.text_location_telephone',
        '{{ $location_address }}' => 'igniter::system.mail_variables.text_location_address',
        '{{ $status_name }}' => 'igniter::system.mail_variables.text_status_name',
        '{{ $status_comment }}' => 'igniter::system.mail_variables.text_status_comment',
        '{{ $order_view_url }}' => 'igniter::system.mail_variables.text_order_view_url',
        '$order_menus[]' => 'igniter::system.mail_variables.text_order_menus',
        '{{ $order_menu[\'menu_name\'] }}' => 'igniter::system.mail_variables.text_menu_name',
        '{{ $order_menu[\'menu_quantity\'] }}' => 'igniter::system.mail_variables.text_menu_quantity',
        '{{ $order_menu[\'menu_price\'] }}' => 'igniter::system.mail_variables.text_menu_price',
        '{{ $order_menu[\'menu_subtotal\'] }}' => 'igniter::system.mail_variables.text_menu_subtotal',
        '{!! $order_menu[\'menu_options\'] !!}' => 'igniter::system.mail_variables.text_menu_options',
        '{{ $order_menu[\'menu_comment\'] }}' => 'igniter::system.mail_variables.text_menu_comment',
        '$order_totals[]' => 'igniter::system.mail_variables.text_order_totals',
        '{{ $order_total[\'order_total_title\'] }}' => 'igniter::system.mail_variables.text_order_total_title',
        '{{ $order_total[\'order_total_value\'] }}' => 'igniter::system.mail_variables.text_order_total_value',
        '{{ $order_total[\'priority\'] }}' => 'igniter::system.mail_variables.text_priority',
        '{{ $order }}' => 'igniter::system.mail_variables.text_order_object',
    ],
    'igniter::system.mail_variables.text_group_reservation' => [
        '{{ $reservation_number }}' => 'igniter::system.mail_variables.text_reservation_number',
        '{{ $reservation_date }}' => 'igniter::system.mail_variables.text_reservation_date',
        '{{ $reservation_time }}' => 'igniter::system.mail_variables.text_reservation_time',
        '{{ $reservation_guest_no }}' => 'igniter::system.mail_variables.text_reservation_guest_no',
        '{{ $first_name }}' => 'igniter::system.mail_variables.text_first_name',
        '{{ $last_name }}' => 'igniter::system.mail_variables.text_last_name',
        '{{ $email }}' => 'igniter::system.mail_variables.text_email',
        '{{ $telephone }}' => 'igniter::system.mail_variables.text_telephone',
        '{{ $reservation_comment }}' => 'igniter::system.mail_variables.text_reservation_comment',
        '{{ $location_name }}' => 'igniter::system.mail_variables.text_location_name',
        '{{ $location_email }}' => 'igniter::system.mail_variables.text_location_email',
        '{{ $location_address }}' => 'igniter::system.mail_variables.text_location_address',
        '{{ $location_telephone }}' => 'igniter::system.mail_variables.text_location_telephone',
        '{{ $status_name }}' => 'igniter::system.mail_variables.text_status_name',
        '{{ $status_comment }}' => 'igniter::system.mail_variables.text_status_comment',
        '{{ $reservation_view_url }}' => 'igniter::system.mail_variables.text_reservation_view_url',
        '{{ $reservation }}' => 'igniter::system.mail_variables.text_reservation_object',
    ],
    'igniter::system.mail_variables.text_group_contact' => [
        '{{ $full_name }}' => 'igniter::system.mail_variables.text_full_name',
        '{{ $contact_email }}' => 'igniter::system.mail_variables.text_contact_email',
        '{{ $contact_telephone }}' => 'igniter::system.mail_variables.text_contact_telephone',
        '{{ $contact_topic }}' => 'igniter::system.mail_variables.text_contact_topic',
        '{{ $contact_message }}' => 'igniter::system.mail_variables.text_contact_message',
    ],
];