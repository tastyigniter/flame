<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class SetupSettings extends FormRequest
{
    public function attributes()
    {
        return [
            'order_email.*' => lang('igniter::system.settings.label_order_email'),
            'processing_order_status' => lang('igniter::system.settings.label_processing_order_status'),
            'completed_order_status' => lang('igniter::system.settings.label_completed_order_status'),
            'canceled_order_status' => lang('igniter::system.settings.label_canceled_order_status'),
            'default_reservation_status' => lang('igniter::system.settings.label_default_reservation_status'),
            'confirmed_reservation_status' => lang('igniter::system.settings.label_confirmed_reservation_status'),
            'canceled_reservation_status' => lang('igniter::system.settings.label_canceled_reservation_status'),
            'menus_page' => lang('igniter::system.settings.label_menus_page'),
            'reservation_page' => lang('igniter::system.settings.label_reservation_page'),
            'guest_order' => lang('igniter::system.settings.label_guest_order'),
            'location_order' => lang('igniter::system.settings.label_location_order'),
            'invoice_prefix' => lang('igniter::system.settings.label_invoice_prefix'),
            'invoice_logo' => lang('igniter::system.settings.label_invoice_logo'),
        ];
    }

    public function rules()
    {
        return [
            'order_email.*' => ['required', 'alpha'],
            'processing_order_status' => ['required', 'array'],
            'completed_order_status' => ['required', 'array'],
            'processing_order_status.*' => ['required', 'integer'],
            'completed_order_status.*' => ['required', 'integer'],
            'canceled_order_status' => ['required', 'integer'],
            'default_reservation_status' => ['required', 'integer'],
            'confirmed_reservation_status' => ['required', 'integer'],
            'canceled_reservation_status' => ['required', 'integer'],
            'guest_order' => ['required', 'integer'],
            'location_order' => ['required', 'integer'],
            'invoice_logo' => ['nullable', 'string'],
        ];
    }
}
