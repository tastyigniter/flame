<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Flame\Exception\ApplicationException;

class Users extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
        \Igniter\Admin\Http\Actions\LocationAwareController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\User::class,
            'title' => 'lang:igniter::admin.staff.text_title',
            'emptyMessage' => 'lang:igniter::admin.staff.text_empty',
            'defaultSort' => ['user_id', 'DESC'],
            'configFile' => 'user',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.staff.text_form_name',
        'model' => \Igniter\Admin\Models\User::class,
        'request' => \Igniter\Admin\Requests\User::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'users/edit/{user_id}',
            'redirectClose' => 'users',
            'redirectNew' => 'users/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'users/edit/{user_id}',
            'redirectClose' => 'users',
            'redirectNew' => 'users/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'users',
        ],
        'delete' => [
            'redirect' => 'users',
        ],
        'configFile' => 'user',
    ];

    protected $requiredPermissions = 'Admin.Staffs';

    public function __construct()
    {
        parent::__construct();

        if ($this->action == 'account') {
            $this->requiredPermissions = null;
        }

        AdminMenu::setContext('users', 'system');
    }

    public function account()
    {
        $this->asExtension('LocationAwareController')->setConfig(['applyScopeOnFormQuery' => false]);

        $this->asExtension('FormController')->edit('account', $this->getUser()->getKey());

        return $this->makeView('edit');
    }

    public function account_onSave()
    {
        $this->asExtension('LocationAwareController')->setConfig(['applyScopeOnFormQuery' => false]);

        $result = $this->asExtension('FormController')->edit_onSave('account', $this->currentUser->user_id);

        $usernameChanged = $this->currentUser->username != post('User[username]');
        $passwordChanged = strlen(post('User[password]'));
        $languageChanged = $this->currentUser->language != post('User[language_id]');
        if ($usernameChanged || $passwordChanged || $languageChanged) {
            $this->currentUser->reload()->reloadRelations();
            AdminAuth::login($this->currentUser, true);
        }

        return $result;
    }

    public function onImpersonate($context, $recordId = null)
    {
        if (!AdminAuth::user()->hasPermission('Admin.Impersonate')) {
            throw new ApplicationException(lang('igniter::admin.staff.alert_login_restricted'));
        }

        $id = post('recordId', $recordId);
        if ($user = $this->formFindModelObject((int)$id)) {
            AdminAuth::stopImpersonate();
            AdminAuth::impersonate($user);
            flash()->success(sprintf(lang('igniter::main.customers.alert_impersonate_success'), $user->name));
        }
    }

    public function listExtendQuery($query)
    {
        if (!AdminAuth::isSuperUser()) {
            $query->whereNotSuperUser();
        }
    }

    public function formExtendQuery($query)
    {
        if (!AdminAuth::isSuperUser()) {
            $query->whereNotSuperUser();
        }
    }

    public function formExtendFields($form)
    {
        if (!AdminAuth::isSuperUser()) {
            $form->removeField('user_role_id');
            $form->removeField('status');
            $form->removeField('super_user');
        }
    }

    public function formAfterSave($model)
    {
        if ($this->status && !$this->is_activated)
            $model->completeActivation($model->getActivationCode());
    }
}
