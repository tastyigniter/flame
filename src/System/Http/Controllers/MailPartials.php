<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;

class MailPartials extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\MailPartial::class,
            'title' => 'lang:igniter::system.mail_templates.text_partial_title',
            'emptyMessage' => 'lang:igniter::system.mail_templates.text_empty',
            'defaultSort' => ['partial_id', 'DESC'],
            'configFile' => 'mailpartial',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::system.mail_templates.text_partial_form_name',
        'model' => \Igniter\System\Models\MailPartial::class,
        'request' => \Igniter\System\Requests\MailPartial::class,
        'create' => [
            'title' => 'lang:igniter::system.mail_templates.text_new_partial_title',
            'redirect' => 'mail_partials/edit/{partial_id}',
            'redirectClose' => 'mail_partials',
            'redirectNew' => 'mail_partials/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::system.mail_templates.text_edit_partial_title',
            'redirect' => 'mail_partials/edit/{partial_id}',
            'redirectClose' => 'mail_partials',
            'redirectNew' => 'mail_partials/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::system.mail_templates.text_preview_partial_title',
            'redirect' => 'mail_partials',
        ],
        'delete' => [
            'redirect' => 'mail_partials',
        ],
        'configFile' => 'mailpartial',
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

    public function formBeforeSave($model)
    {
        $model->is_custom = true;
    }
}
