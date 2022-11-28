<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;

class MenuOptions extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
        \Igniter\Admin\Http\Actions\LocationAwareController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\MenuOption::class,
            'title' => 'lang:igniter::admin.menu_options.text_title',
            'emptyMessage' => 'lang:igniter::admin.menu_options.text_empty',
            'defaultSort' => ['option_id', 'DESC'],
            'configFile' => 'menuoption',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.menu_options.text_form_name',
        'model' => \Igniter\Admin\Models\MenuOption::class,
        'request' => \Igniter\Admin\Requests\MenuOption::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'menu_options/edit/{option_id}',
            'redirectClose' => 'menu_options',
            'redirectNew' => 'menu_options/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'menu_options/edit/{option_id}',
            'redirectClose' => 'menu_options',
            'redirectNew' => 'menu_options/create',
        ],
        'preview' => [
            'title' => 'lang:admin::default.form.preview_title',
            'redirect' => 'menu_options',
        ],
        'delete' => [
            'redirect' => 'menu_options',
        ],
        'configFile' => 'menuoption',
    ];

    protected $requiredPermissions = 'Admin.Menus';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('menus', 'restaurant');
    }

    public function edit($context = null, $recordId = null)
    {
        $this->addJs('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');

        $this->asExtension('FormController')->edit($context, $recordId);
    }
}
