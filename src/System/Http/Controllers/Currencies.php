<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;

class Currencies extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\Currency::class,
            'title' => 'lang:igniter::system.currencies.text_title',
            'emptyMessage' => 'lang:igniter::system.currencies.text_empty',
            'defaultSort' => ['currency_status', 'DESC'],
            'configFile' => 'currency',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::system.currencies.text_form_name',
        'model' => \Igniter\System\Models\Currency::class,
        'request' => \Igniter\System\Requests\Currency::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'currencies/edit/{currency_id}',
            'redirectClose' => 'currencies',
            'redirectNew' => 'currencies/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'currencies/edit/{currency_id}',
            'redirectClose' => 'currencies',
            'redirectNew' => 'currencies/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'currencies',
        ],
        'delete' => [
            'redirect' => 'currencies',
        ],
        'configFile' => 'currency',
    ];

    protected $requiredPermissions = 'Site.Currencies';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('currencies', 'localisation');
    }
}
