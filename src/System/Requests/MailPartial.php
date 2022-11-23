<?php

namespace Igniter\System\Requests;

use Igniter\System\Classes\FormRequest;

class MailPartial extends FormRequest
{
    public function attributes()
    {
        return [
            'name' => lang('igniter::admin.label_name'),
            'code' => lang('igniter::system.mail_templates.label_code'),
            'html' => lang('igniter::system.mail_templates.label_html'),
        ];
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string'],
            'code' => ['sometimes', 'required', 'regex:/^[a-z-_\.\:]+$/i', 'unique:mail_partials'],
            'html' => ['required', 'string'],
        ];
    }
}
