<?php

namespace Igniter\System\Requests;

use Igniter\System\Classes\FormRequest;

class Language extends FormRequest
{
    public function attributes()
    {
        return [
            'name' => lang('igniter::admin.label_name'),
            'code' => lang('igniter::system.languages.label_code'),
            'status' => lang('igniter::admin.label_status'),
            'translations.*.source' => lang('igniter::system.column_source'),
            'translations.*.translation' => lang('igniter::system.column_translation'),
        ];
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'between:2,32'],
            'code' => ['required', 'regex:/^[a-zA-Z_]+$/', 'unique:languages'],
            'status' => ['required', 'boolean'],
            'translations.*.source' => ['string', 'max:2500'],
            'translations.*.translation' => ['string', 'max:2500'],
        ];
    }

    protected function useDataFrom()
    {
        return static::DATA_TYPE_POST;
    }
}
