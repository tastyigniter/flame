<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Models\Category;

class Categories extends AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
        \Igniter\Admin\Http\Actions\LocationAwareController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\Category::class,
            'title' => 'lang:igniter::admin.categories.text_title',
            'emptyMessage' => 'lang:igniter::admin.categories.text_empty',
            'defaultSort' => ['category_id', 'DESC'],
            'configFile' => 'category',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.categories.text_form_name',
        'model' => \Igniter\Admin\Models\Category::class,
        'request' => \Igniter\Admin\Requests\Category::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'categories/edit/{category_id}',
            'redirectClose' => 'categories',
            'redirectNew' => 'categories/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'categories/edit/{category_id}',
            'redirectClose' => 'categories',
            'redirectNew' => 'categories/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'categories',
        ],
        'delete' => [
            'redirect' => 'categories',
        ],
        'configFile' => 'category',
    ];

    protected $requiredPermissions = ['Admin.Categories'];

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('categories', 'restaurant');
    }

    public function formBeforeSave($model)
    {
        if (!$model->getRgt() || !$model->getLft())
            $model->fixTree();

        if (Category::isBroken())
            Category::fixTree();
    }
}
