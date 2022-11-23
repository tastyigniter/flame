<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class Mealtime extends FormRequest
{
    public function attributes()
    {
        return [
            'mealtime_name' => lang('igniter::admin.mealtimes.label_mealtime_name'),
            'start_time' => lang('igniter::admin.mealtimes.label_start_time'),
            'end_time' => lang('igniter::admin.mealtimes.label_end_time'),
            'mealtime_status' => lang('igniter::admin.label_status'),
            'locations.*' => lang('igniter::admin.label_location'),
        ];
    }

    public function rules()
    {
        return [
            'mealtime_name' => ['required', 'string', 'between:2,128'],
            'start_time' => ['required', 'valid_time'],
            'end_time' => ['required', 'valid_time'],
            'mealtime_status' => ['required', 'boolean'],
            'locations.*' => ['integer'],
        ];
    }
}
