<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;

class Statuses extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\Status::class,
            'title' => 'lang:igniter::admin.statuses.text_title',
            'emptyMessage' => 'lang:igniter::admin.statuses.text_empty',
            'defaultSort' => ['status_id', 'DESC'],
            'configFile' => 'status',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.statuses.text_form_name',
        'model' => \Igniter\Admin\Models\Status::class,
        'request' => \Igniter\Admin\Requests\Status::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'statuses/edit/{status_id}',
            'redirectClose' => 'statuses',
            'redirectNew' => 'statuses/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'statuses/edit/{status_id}',
            'redirectClose' => 'statuses',
            'redirectNew' => 'statuses/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'statuses',
        ],
        'delete' => [
            'redirect' => 'statuses',
        ],
        'configFile' => 'status',
    ];

    protected $requiredPermissions = 'Admin.Statuses';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('statuses', 'sales');
    }
}
