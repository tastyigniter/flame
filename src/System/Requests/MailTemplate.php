<?php

namespace Igniter\System\Requests;

use Igniter\System\Classes\FormRequest;

class MailTemplate extends FormRequest
{
    public function attributes()
    {
        return [
            'layout_id' => lang('igniter::system.mail_templates.label_layout'),
            'label' => lang('igniter::admin.label_description'),
            'subject' => lang('igniter::system.mail_templates.label_subject'),
            'code' => lang('igniter::system.mail_templates.label_code'),
        ];
    }

    public function rules()
    {
        return [
            'layout_id' => ['integer'],
            'code' => ['sometimes', 'required', 'min:2', 'max:128', 'unique:mail_templates', 'regex:/^[a-z-_\.\:]+$/i'],
            'label' => ['required', 'string'],
            'subject' => ['required', 'string'],
            'body' => ['string'],
            'plain_body' => ['string'],
        ];
    }
}
