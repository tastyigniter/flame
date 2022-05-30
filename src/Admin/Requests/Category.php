<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class Category extends FormRequest
{
    public function attributes()
    {
        return [
            'name' => lang('igniter::admin.label_name'),
            'description' => lang('igniter::admin.label_description'),
            'permalink_slug' => lang('igniter::admin.categories.label_permalink_slug'),
            'parent_id' => lang('igniter::admin.categories.label_parent'),
            'priority' => lang('igniter::admin.categories.label_priority'),
            'status' => lang('igniter::admin.label_status'),
            'locations.*' => lang('igniter::admin.column_location'),
        ];
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'between:2,128', 'unique:categories'],
            'description' => ['string', 'min:2'],
            'permalink_slug' => ['alpha_dash', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'integer'],
            'status' => ['boolean'],
            'locations' => ['array'],
            'locations.*' => ['integer'],
        ];
    }
}
