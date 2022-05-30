<?php

namespace Igniter\System\Requests;

use Igniter\System\Classes\FormRequest;

class MailLayout extends FormRequest
{
    public function attributes()
    {
        return [
            'name' => lang('igniter::admin.label_name'),
            'code' => lang('igniter::system.mail_templates.label_code'),
            'layout' => lang('igniter::system.mail_templates.label_body'),
            'layout_css' => lang('igniter::system.mail_templates.label_layout_css'),
            'plain_layout' => lang('igniter::system.mail_templates.label_plain'),
        ];
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'between:2,32'],
            'code' => ['required', 'alpha_dash'],
            'layout' => ['string'],
            'layout_css' => ['string'],
            'plain_layout' => ['string'],
        ];
    }
}
