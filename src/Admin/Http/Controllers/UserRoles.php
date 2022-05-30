<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;

class UserRoles extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\UserRole::class,
            'title' => 'lang:igniter::admin.user_roles.text_title',
            'emptyMessage' => 'lang:igniter::admin.user_roles.text_empty',
            'defaultSort' => ['user_role_id', 'DESC'],
            'configFile' => 'userrole',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.user_roles.text_form_name',
        'model' => \Igniter\Admin\Models\UserRole::class,
        'request' => \Igniter\Admin\Requests\UserRole::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'user_roles/edit/{user_role_id}',
            'redirectClose' => 'user_roles',
            'redirectNew' => 'user_roles/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'user_roles/edit/{user_role_id}',
            'redirectClose' => 'user_roles',
            'redirectNew' => 'user_roles/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'user_roles',
        ],
        'delete' => [
            'redirect' => 'user_roles',
        ],
        'configFile' => 'userrole',
    ];

    protected $requiredPermissions = 'Admin.Staffs';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('users', 'system');
    }
}
