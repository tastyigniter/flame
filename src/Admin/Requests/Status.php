<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class Status extends FormRequest
{
    public function attributes()
    {
        return [
            'status_name' => lang('igniter::admin.label_name'),
            'status_for' => lang('igniter::admin.statuses.label_for'),
            'status_color' => lang('igniter::admin.statuses.label_color'),
            'status_comment' => lang('igniter::admin.statuses.label_comment'),
            'notify_customer' => lang('igniter::admin.statuses.label_notify'),
        ];
    }

    public function rules()
    {
        return [
            'status_name' => ['required', 'string', 'between:2,32'],
            'status_for' => ['required', 'in:order,reservation'],
            'status_color' => ['string', 'max:7'],
            'status_comment' => ['string', 'max:1028'],
            'notify_customer' => ['required', 'boolean'],
        ];
    }
}
