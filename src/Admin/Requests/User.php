<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class User extends FormRequest
{
    public function attributes()
    {
        return [
            'name' => lang('igniter::admin.label_name'),
            'email' => lang('igniter::admin.label_email'),
            'username' => lang('igniter::admin.staff.label_username'),
            'password' => lang('igniter::admin.staff.label_password'),
            'password_confirm' => lang('igniter::admin.staff.label_confirm_password'),
            'status' => lang('igniter::admin.label_status'),
            'language_id' => lang('igniter::admin.staff.label_language_id'),
            'user_role_id' => lang('igniter::admin.staff.label_role'),
            'groups' => lang('igniter::admin.staff.label_group'),
            'locations' => lang('igniter::admin.staff.label_location'),
            'groups.*' => lang('igniter::admin.staff.label_group'),
            'locations.*' => lang('igniter::admin.staff.label_location'),
        ];
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'between:2,128'],
            'email' => ['required', 'max:96', 'email:filter', 'unique:users,email'],
            'username' => ['required', 'alpha_dash', 'between:2,32', 'unique:users,username'],
            'password' => ['sometimes', 'required_if:send_invite,0', 'string', 'between:6,32', 'same:password_confirm'],
            'status' => ['boolean'],
            'language_id' => ['nullable', 'integer'],
            'user_role_id' => ['sometimes', 'required', 'integer'],
            'groups' => ['sometimes', 'required', 'array'],
            'locations' => ['nullable', 'array'],
            'groups.*' => ['integer'],
            'locations.*' => ['integer'],
        ];
    }
}
