<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;

class Ingredients extends AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
        \Igniter\Admin\Http\Actions\LocationAwareController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\Ingredient::class,
            'title' => 'lang:igniter::admin.ingredients.text_title',
            'emptyMessage' => 'lang:igniter::admin.ingredients.text_empty',
            'defaultSort' => ['ingredient_id', 'DESC'],
            'configFile' => 'ingredient',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.ingredients.text_form_name',
        'model' => \Igniter\Admin\Models\Ingredient::class,
        'request' => \Igniter\Admin\Requests\Ingredient::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'ingredients/edit/{ingredient_id}',
            'redirectClose' => 'ingredients',
            'redirectNew' => 'ingredients/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'ingredients/edit/{ingredient_id}',
            'redirectClose' => 'ingredients',
            'redirectNew' => 'ingredients/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'ingredients',
        ],
        'delete' => [
            'redirect' => 'ingredients',
        ],
        'configFile' => 'ingredient',
    ];

    protected $requiredPermissions = 'Admin.Ingredients';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('menus', 'restaurant');
    }
}
