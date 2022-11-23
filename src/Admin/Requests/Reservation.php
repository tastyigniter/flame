<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class Reservation extends FormRequest
{
    public function attributes()
    {
        return [
            'location_id' => lang('igniter::admin.reservations.text_restaurant'),
            'first_name' => lang('igniter::admin.reservations.label_first_name'),
            'last_name' => lang('igniter::admin.reservations.label_last_name'),
            'email' => lang('igniter::admin.label_email'),
            'telephone' => lang('igniter::admin.reservations.label_customer_telephone'),
            'reserve_date' => lang('igniter::admin.reservations.label_reservation_date'),
            'reserve_time' => lang('igniter::admin.reservations.label_reservation_time'),
            'guest_num' => lang('igniter::admin.reservations.label_guest'),
        ];
    }

    public function rules()
    {
        return [
            'location_id' => ['sometimes', 'required', 'integer'],
            'first_name' => ['required', 'string', 'between:1,48'],
            'last_name' => ['required', 'string', 'between:1,48'],
            'email' => ['email:filter', 'max:96'],
            'telephone' => ['sometimes', 'string'],
            'reserve_date' => ['required', 'valid_date'],
            'reserve_time' => ['required', 'valid_time'],
            'guest_num' => ['required', 'integer'],
            'duration' => ['integer', 'min:1'],
        ];
    }
}
