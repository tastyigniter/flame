<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class Table extends FormRequest
{
    public function attributes()
    {
        return [
            'table_name' => lang('igniter::admin.label_name'),
            'min_capacity' => lang('igniter::admin.tables.label_min_capacity'),
            'max_capacity' => lang('igniter::admin.tables.label_capacity'),
            'extra_capacity' => lang('igniter::admin.tables.label_extra_capacity'),
            'priority' => lang('igniter::admin.tables.label_priority'),
            'is_joinable' => lang('igniter::admin.tables.label_joinable'),
            'table_status' => lang('igniter::admin.label_status'),
            'locations' => lang('igniter::admin.label_location'),
            'locations.*' => lang('igniter::admin.label_location'),
        ];
    }

    public function rules()
    {
        return [
            'table_name' => ['required', 'string', 'between:2,255', 'unique:tables'],
            'min_capacity' => ['required', 'integer', 'min:1', 'lte:max_capacity'],
            'max_capacity' => ['required', 'integer', 'min:1', 'gte:min_capacity'],
            'extra_capacity' => ['required', 'integer'],
            'priority' => ['required', 'integer'],
            'is_joinable' => ['required', 'boolean'],
            'table_status' => ['required', 'boolean'],
            'locations' => ['required', 'array'],
            'locations.*' => ['integer'],
        ];
    }
}
