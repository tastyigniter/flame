<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class Ingredient extends FormRequest
{
    public function attributes()
    {
        return [
            'name' => lang('igniter::admin.label_name'),
            'description' => lang('igniter::admin.label_description'),
            'status' => lang('igniter::admin.label_status'),
            'is_allergen' => lang('igniter::admin.ingredients.label_allergen'),
        ];
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'between:2,128'],
            'description' => ['string', 'min:2'],
            'status' => ['boolean'],
            'is_allergen' => ['boolean'],
        ];
    }
}
