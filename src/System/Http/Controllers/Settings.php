<?php

namespace Igniter\System\Http\Controllers;

use Exception;
use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Traits\FormExtendable;
use Igniter\Admin\Traits\WidgetMaker;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\System\Models\MailTemplate;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class Settings extends \Igniter\Admin\Classes\AdminController
{
    use WidgetMaker;
    use FormExtendable;

    protected $requiredPermissions = 'Site.Settings';

    protected $modelClass = \Igniter\System\Models\Settings::class;

    /**
     * @var \Igniter\Admin\Widgets\Form
     */
    public $formWidget;

    /**
     * @var \Igniter\Admin\Widgets\Toolbar
     */
    public $toolbarWidget;

    public $settingCode;

    public $settingItemErrors = [];

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('settings', 'system');
    }

    public function index()
    {
        MailTemplate::syncAll();

        $this->validateSettingItems(true);

        // For security reasons, delete setup files if still exists.
        if (File::isFile(base_path('setup.php')) || File::isDirectory(base_path('setup'))) {
            flash()->danger(lang('igniter::system.settings.alert_delete_setup_files'))->important()->now();
        }

        $pageTitle = lang('igniter::system.settings.text_title');
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);
        $this->vars['settings'] = $this->createModel()->listSettingItems();
        $this->vars['settingItemErrors'] = $this->settingItemErrors;
    }

    public function edit($context, $settingCode = null)
    {
        try {
            $this->settingCode = $settingCode;
            [$model, $definition] = $this->findSettingDefinitions($settingCode);
            if (!$definition) {
                throw new Exception(sprintf(lang('igniter::system.settings.alert_settings_not_found'), $settingCode));
            }

            if ($definition->permission && !AdminAuth::user()->hasPermission($definition->permission))
                return Response::make(View::make('admin::access_denied'), 403);

            $pageTitle = sprintf(lang('igniter::system.settings.text_edit_title'), lang($definition->label));
            Template::setTitle($pageTitle);
            Template::setHeading($pageTitle);

            $this->initWidgets($model, $definition);

            $this->validateSettingItems();
            if ($errors = array_get($this->settingItemErrors, $settingCode))
                Session::flash('errors', $errors);
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function edit_onSave($context, $settingCode = null)
    {
        [$model, $definition] = $this->findSettingDefinitions($settingCode);
        if (!$definition) {
            throw new Exception(lang('igniter::system.settings.alert_settings_not_found'));
        }

        if ($definition->permission && !AdminAuth::user()->hasPermission($definition->permission))
            return Response::make(View::make('admin::access_denied'), 403);

        $this->initWidgets($model, $definition);

        $this->validateFormRequest($model, $definition);

        if ($this->formValidate($model, $this->formWidget) === false)
            return Request::ajax() ? ['#notification' => $this->makePartial('flash')] : false;

        $this->formBeforeSave($model);

        setting()->set($this->formWidget->getSaveData());
        setting()->save();

        $this->formAfterSave($model);

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang($definition->label).' settings updated '));

        if (post('close')) {
            return $this->redirect('settings');
        }

        return $this->refresh();
    }

    public function edit_onTestMail()
    {
        [$model, $definition] = $this->findSettingDefinitions('mail');
        if (!$definition) {
            throw new Exception(lang('igniter::system.settings.alert_settings_not_found'));
        }

        $this->initWidgets($model, $definition);

        $this->validateFormRequest($model, $definition);

        if ($this->formValidate($model, $this->formWidget) === false)
            return Request::ajax() ? ['#notification' => $this->makePartial('flash')] : false;

        setting()->set($this->formWidget->getSaveData());

        $name = AdminAuth::getStaffName();
        $email = AdminAuth::getStaffEmail();

        try {
            Mail::raw(lang('igniter::system.settings.text_test_email_message'), function (Message $message) use ($name, $email) {
                $message->to($email, $name)->subject('This a test email');
            });

            flash()->success(sprintf(lang('igniter::system.settings.alert_email_sent'), $email));
        }
        catch (Exception $ex) {
            flash()->error($ex->getMessage());
        }

        return $this->refresh();
    }

    public function initWidgets($model, $definition)
    {
        $modelConfig = $this->getFieldConfig($definition->code, $model);

        $formConfig = array_except($modelConfig, 'toolbar');
        $formConfig['model'] = $model;
        $formConfig['data'] = array_undot($model->getFieldValues());
        $formConfig['alias'] = 'form';
        $formConfig['arrayName'] = str_singular(strip_class_basename($model, '_model'));
        $formConfig['context'] = 'edit';

        // Form Widget with extensibility
        $this->formWidget = $this->makeWidget(\Igniter\Admin\Widgets\Form::class, $formConfig);
        $this->formWidget->bindToController();

        // Prep the optional toolbar widget
        if (isset($modelConfig['toolbar']) && isset($this->widgets['toolbar'])) {
            $this->toolbarWidget = $this->widgets['toolbar'];
            $this->toolbarWidget->reInitialize($modelConfig['toolbar']);
        }
    }

    protected function findSettingDefinitions($code)
    {
        if (!strlen($code))
            throw new Exception(lang('igniter::admin.form.missing_id'));

        // Prep the list widget config
        $model = $this->createModel();

        $definition = $model->getSettingDefinitions($code);

        return [$model, $definition];
    }

    protected function createModel()
    {
        if (!isset($this->modelClass) || !strlen($this->modelClass)) {
            throw new Exception(lang('igniter::system.settings.alert_settings_missing_model'));
        }

        return new $this->modelClass();
    }

    protected function formAfterSave($model)
    {
        $this->validateSettingItems(true);
    }

    protected function getFieldConfig($code, $model)
    {
        $settingItem = $model->getSettingItem('core.'.$code);
        if (!is_array($settingItem->form))
            $settingItem->form = array_get($this->makeConfig($settingItem->form, ['form']), 'form', []);

        return $settingItem->form ?? [];
    }

    protected function validateSettingItems($skipSession = false)
    {
        $settingItemErrors = Session::get('settings.errors', []);

        if ($skipSession || !$settingItemErrors) {
            $model = $this->createModel();
            $settingItems = array_get($model->listSettingItems(), 'core');
            $settingValues = array_undot($model->getFieldValues());

            foreach ($settingItems as $settingItem) {
                $settingItemForm = $this->getFieldConfig($settingItem->code, $this->createModel());

                if (!isset($settingItemForm['rules']))
                    continue;

                $validator = $this->makeValidator($settingValues, $settingItemForm['rules']);
                $errors = $validator->fails() ? $validator->errors() : [];

                $settingItemErrors[$settingItem->code] = $errors;
            }

            Session::put('settings.errors', $settingItemErrors);
        }

        return $this->settingItemErrors = $settingItemErrors;
    }

    protected function validateFormRequest($model, $definition)
    {
        if (!strlen($requestClass = $definition->request))
            return;

        if (!class_exists($requestClass))
            throw new ApplicationException(sprintf(lang('igniter::admin.form.request_class_not_found'), $requestClass));

        app()->resolving($requestClass, function ($request, $app) {
            if (method_exists($request, 'setController'))
                $request->setController($this);

            $request->setInputKey('Setting');
        });

        return app()->make($requestClass);
    }
}
