<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Models\UserGroup;

class UserGroups extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\UserGroup::class,
            'title' => 'lang:igniter::admin.user_groups.text_title',
            'emptyMessage' => 'lang:igniter::admin.user_groups.text_empty',
            'defaultSort' => ['user_group_id', 'DESC'],
            'configFile' => 'usergroup',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.user_groups.text_form_name',
        'model' => \Igniter\Admin\Models\UserGroup::class,
        'request' => \Igniter\Admin\Requests\UserGroup::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'user_groups/edit/{user_group_id}',
            'redirectClose' => 'user_groups',
            'redirectNew' => 'user_groups/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'user_groups/edit/{user_group_id}',
            'redirectClose' => 'user_groups',
            'redirectNew' => 'user_groups/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'user_groups',
        ],
        'delete' => [
            'redirect' => 'user_groups',
        ],
        'configFile' => 'usergroup',
    ];

    protected $requiredPermissions = 'Admin.StaffGroups';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('users', 'system');
    }

    public function formAfterSave()
    {
        UserGroup::syncAutoAssignStatus();
    }
}
