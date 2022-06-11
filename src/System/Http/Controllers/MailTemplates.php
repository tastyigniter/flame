<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\System\Models\MailTemplate;
use Illuminate\Support\Facades\Mail;

class MailTemplates extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\MailTemplate::class,
            'title' => 'lang:igniter::system.mail_templates.text_template_title',
            'emptyMessage' => 'lang:igniter::system.mail_templates.text_empty',
            'defaultSort' => ['template_id', 'DESC'],
            'configFile' => 'mailtemplate',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::system.mail_templates.text_form_name',
        'model' => \Igniter\System\Models\MailTemplate::class,
        'request' => \Igniter\System\Requests\MailTemplate::class,
        'create' => [
            'title' => 'lang:igniter::system.mail_templates.text_new_template_title',
            'redirect' => 'mail_templates/edit/{template_id}',
            'redirectClose' => 'mail_templates',
            'redirectNew' => 'mail_templates/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::system.mail_templates.text_edit_template_title',
            'redirect' => 'mail_templates/edit/{template_id}',
            'redirectClose' => 'mail_templates',
            'redirectNew' => 'mail_templates/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::system.mail_templates.text_preview_template_title',
            'redirect' => 'mail_templates/preview/{template_id}',
        ],
        'delete' => [
            'redirect' => 'mail_templates',
        ],
        'configFile' => 'mailtemplate',
    ];

    protected $requiredPermissions = 'Admin.MailTemplates';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('mail_templates', 'design');
    }

    public function index()
    {
        MailTemplate::syncAll();

        $this->asExtension('ListController')->index();
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

    public function onTestTemplate($context, $recordId)
    {
        if (!strlen($recordId))
            throw new ApplicationException(lang('igniter::system.mail_templates.alert_template_id_not_found'));

        if (!$model = $this->formFindModelObject($recordId))
            throw new ApplicationException(lang('igniter::system.mail_templates.alert_template_not_found'));

        $adminUser = $this->getUser();

        config()->set('igniter.system.suppressTemplateRuntimeNotice', true);

        Mail::sendTemplate($model->code, $model->getDummyData(), [$adminUser->email, $adminUser->name]);

        flash()->success(sprintf(lang('igniter::system.mail_templates.alert_test_message_sent'), $adminUser->staff_email));

        return [
            '#notification' => $this->makePartial('flash'),
        ];
    }
}
