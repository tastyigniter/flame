<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;

/**
 * Admin Controller Class Tables
 */
class Tables extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
        \Igniter\Admin\Http\Actions\LocationAwareController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\Table::class,
            'title' => 'lang:igniter::admin.tables.text_title',
            'emptyMessage' => 'lang:igniter::admin.tables.text_empty',
            'defaultSort' => ['table_id', 'DESC'],
            'configFile' => 'table',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.tables.text_form_name',
        'model' => \Igniter\Admin\Models\Table::class,
        'request' => \Igniter\Admin\Requests\Table::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'tables/edit/{table_id}',
            'redirectClose' => 'tables',
            'redirectNew' => 'tables/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'tables/edit/{table_id}',
            'redirectClose' => 'tables',
            'redirectNew' => 'tables/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'tables',
        ],
        'delete' => [
            'redirect' => 'tables',
        ],
        'configFile' => 'table',
    ];

    protected $requiredPermissions = 'Admin.Tables';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('tables', 'restaurant');
    }
}
