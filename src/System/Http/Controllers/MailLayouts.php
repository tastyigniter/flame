<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;

class MailLayouts extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\MailLayout::class,
            'title' => 'lang:igniter::system.mail_templates.text_title',
            'emptyMessage' => 'lang:igniter::system.mail_templates.text_empty',
            'defaultSort' => ['layout_id', 'DESC'],
            'configFile' => 'maillayout',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::system.mail_templates.text_form_name',
        'model' => \Igniter\System\Models\MailLayout::class,
        'request' => \Igniter\System\Requests\MailLayout::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'mail_layouts/edit/{layout_id}',
            'redirectClose' => 'mail_layouts',
            'redirectNew' => 'mail_layouts/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'mail_layouts/edit/{layout_id}',
            'redirectClose' => 'mail_layouts',
            'redirectNew' => 'mail_layouts/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'mail_layouts',
        ],
        'delete' => [
            'redirect' => 'mail_layouts',
        ],
        'configFile' => 'maillayout',
    ];

    protected $requiredPermissions = 'Admin.MailTemplates';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('mail_templates', 'design');
    }

    public function formExtendFields($form)
    {
        if ($form->context != 'create') {
            $field = $form->getField('code');
            $field->disabled = true;
        }
    }
}
