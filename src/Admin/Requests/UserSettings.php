<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class UserSettings extends FormRequest
{
    public function attributes()
    {
        return [
            'allow_registration' => lang('igniter::system.settings.label_allow_registration'),
            'registration_email.*' => lang('igniter::system.settings.label_registration_email'),
        ];
    }

    public function rules()
    {
        return [
            'allow_registration' => ['required', 'integer'],
            'registration_email' => ['required', 'array'],
            'registration_email.*' => ['required', 'alpha'],
        ];
    }
}
