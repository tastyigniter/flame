<?php

namespace Igniter\System\Http\Controllers;

use Exception;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Traits\WidgetMaker;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\SystemException;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Models\Extension;
use Igniter\System\Models\Settings;
use Igniter\System\Traits\ManagesUpdates;
use Illuminate\Support\Facades\Request;

class Extensions extends \Igniter\Admin\Classes\AdminController
{
    use WidgetMaker;
    use ManagesUpdates;

    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\Extension::class,
            'title' => 'lang:igniter::system.extensions.text_title',
            'emptyMessage' => 'lang:igniter::system.extensions.text_empty',
            'pageLimit' => 50,
            'defaultSort' => ['name', 'ASC'],
            'showCheckboxes' => false,
            'configFile' => 'extension',
        ],
    ];

    protected $requiredPermissions = ['Admin.Extensions', 'Site.Settings'];

    /**
     * @var \Igniter\Admin\Widgets\Form
     */
    public $formWidget;

    /**
     * @var \Igniter\Admin\Widgets\Toolbar
     */
    public $toolbarWidget;

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('extensions', 'system');
    }

    public function index()
    {
        if (!$this->getUser()->hasPermission('Admin.Extensions'))
            throw new SystemException(lang('igniter::admin.alert_user_restricted'));

        Extension::syncAll();

        $this->initUpdate('extension');

        $this->asExtension('ListController')->index();
    }

    public function edit($action, $vendor = null, $extension = null, $context = null)
    {
        if (!$this->getUser()->hasPermission('Site.Settings'))
            throw new SystemException(lang('igniter::admin.alert_user_restricted'));

        AdminMenu::setContext('settings', 'system');

        try {
            if (!strlen($vendor) || !strlen($extension)) {
                throw new SystemException(lang('igniter::system.extensions.alert_setting_missing_id'));
            }

            $extensionCode = $vendor.'.'.$extension.'.'.$context;
            if (!$settingItem = Settings::make()->getSettingItem($extensionCode)) {
                throw new SystemException(lang('igniter::system.extensions.alert_setting_not_found'));
            }

            if ($settingItem->permissions && !$this->getUser()->hasPermission($settingItem->permissions))
                throw new SystemException(lang('igniter::admin.alert_user_restricted'));

            $pageTitle = lang($settingItem->label ?: 'text_edit_title');
            Template::setTitle($pageTitle);
            Template::setHeading($pageTitle);

            $model = $this->formFindModelObject($settingItem);

            $this->initFormWidget($model, $action);
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function delete($context, $extensionCode = null)
    {
        if (!$this->getUser()->hasPermission('Admin.Extensions'))
            throw new SystemException(lang('igniter::admin.alert_user_restricted'));

        try {
            $pageTitle = lang('igniter::system.extensions.text_delete_title');
            Template::setTitle($pageTitle);
            Template::setHeading($pageTitle);

            $extensionManager = resolve(ExtensionManager::class);
            $extensionClass = $extensionManager->findExtension($extensionCode);

            // Extension not found in filesystem
            // so delete from database
            if (!$extensionClass) {
                $extensionManager->deleteExtension($extensionCode);
                flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Extension deleted '));

                return $this->redirectBack();
            }

            // Extension must be disabled before it can be deleted
            if (!$extensionClass->disabled) {
                flash()->warning(sprintf(lang('igniter::admin.alert_error_nothing'), lang('igniter::admin.text_deleted').lang('igniter::system.extensions.alert_is_installed')));

                return $this->redirectBack();
            }

            // Lets display a delete confirmation screen
            // with list of files to be deleted
            $meta = $extensionClass->extensionMeta();
            $this->vars['extensionModel'] = Extension::where('name', $extensionCode)->first();
            $this->vars['extensionMeta'] = $meta;
            $this->vars['extensionName'] = $meta['name'] ?? '';
            $this->vars['extensionData'] = $this->extensionHasMigrations($extensionCode);
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function index_onInstall($context = null)
    {
        if (!$extensionCode = trim(post('code')))
            throw new ApplicationException(lang('igniter::admin.alert_error_try_again'));

        $manager = resolve(ExtensionManager::class);
        $extension = $manager->findExtension($extensionCode);
        if ($feedback = $this->checkDependencies($extension))
            throw new ApplicationException($feedback);

        if ($manager->installExtension($extensionCode)) {
            $title = array_get($extension->extensionMeta(), 'name');
            flash()->success(sprintf(lang('igniter::admin.alert_success'), "Extension {$title} installed "));
        }
        else {
            flash()->danger(lang('igniter::admin.alert_error_try_again'));
        }

        return $this->refreshList('list');
    }

    public function index_onUninstall($context = null)
    {
        if (!$extensionCode = trim(post('code')))
            throw new ApplicationException(lang('igniter::admin.alert_error_try_again'));

        $manager = resolve(ExtensionManager::class);
        $extension = $manager->findExtension($extensionCode);

        if ($manager->uninstallExtension($extensionCode)) {
            $title = $extension ? array_get($extension->extensionMeta(), 'name') : $extensionCode;
            flash()->success(sprintf(lang('igniter::admin.alert_success'), "Extension {$title} uninstalled "));
        }
        else {
            flash()->danger(lang('igniter::admin.alert_error_try_again'));
        }

        return $this->refreshList('list');
    }

    public function edit_onSave($action, $vendor = null, $extension = null, $context = null)
    {
        if (!strlen($vendor) || !strlen($extension)) {
            throw new SystemException(lang('igniter::system.extensions.alert_setting_missing_id'));
        }

        $extensionCode = $vendor.'.'.$extension.'.'.$context;
        if (!$settingItem = Settings::make()->getSettingItem($extensionCode)) {
            throw new SystemException(lang('igniter::system.extensions.alert_setting_not_found'));
        }

        if ($settingItem->permissions && !$this->getUser()->hasPermission($settingItem->permissions))
            throw new SystemException(lang('igniter::admin.alert_user_restricted'));

        $model = $this->formFindModelObject($settingItem);

        $this->initFormWidget($model, $action);

        if ($this->formValidate($model, $this->formWidget) === false)
            return Request::ajax() ? ['#notification' => $this->makePartial('flash')] : false;

        $saved = $model->set($this->formWidget->getSaveData());
        if ($saved) {
            flash()->success(sprintf(lang('igniter::admin.alert_success'), lang($settingItem->label).' settings updated '));
        }
        else {
            flash()->warning(sprintf(lang('igniter::admin.alert_error_nothing'), 'updated'));
        }

        if (post('close')) {
            return $this->redirect('settings');
        }

        return $this->refresh();
    }

    public function delete_onDelete($context = null, $extensionCode = null)
    {
        $manager = resolve(ExtensionManager::class);
        if (!$extension = $manager->findExtension($extensionCode))
            throw new ApplicationException(lang('igniter::admin.alert_error_try_again'));

        $purgeData = post('delete_data') == 1;
        if ($manager->deleteExtension($extensionCode, $purgeData)) {
            $title = array_get($extension->extensionMeta(), 'name');
            flash()->success(sprintf(lang('igniter::admin.alert_success'), "Extension {$title} deleted "));
        }
        else {
            flash()->danger(lang('igniter::admin.alert_error_try_again'));
        }

        return $this->redirect('extensions');
    }

    public function listOverrideColumnValue($record, $column, $alias = null)
    {
        if ($column->type != 'button')
            return null;

        if (($column->columnName == 'delete' && $record->status) || ($column->columnName != 'delete' && !$record->class)) {
            $attributes = $column->attributes;
            $attributes['class'] .= ' disabled';

            return $attributes;
        }
    }

    protected function initFormWidget($model, $context = null)
    {
        $config = $model->getFieldConfig();

        $modelConfig = array_except($config, 'toolbar');
        $modelConfig['model'] = $model;
        $modelConfig['arrayName'] = str_singular(strip_class_basename($model, '_model'));
        $modelConfig['context'] = $context;

        // Form Widget with extensibility
        $this->formWidget = $this->makeWidget(\Igniter\Admin\Widgets\Form::class, $modelConfig);
        $this->formWidget->bindToController();

        // Prep the optional toolbar widget
        if (isset($config['toolbar']) && isset($this->widgets['toolbar'])) {
            $this->toolbarWidget = $this->widgets['toolbar'];
            $this->toolbarWidget->reInitialize($config['toolbar']);
        }
    }

    protected function createModel($class)
    {
        if (!strlen($class))
            throw new SystemException(lang('igniter::system.extensions.alert_setting_model_missing'));

        if (!class_exists($class))
            throw new SystemException(sprintf(lang('igniter::system.extensions.alert_setting_model_not_found'), $class));

        $model = new $class;

        return $model;
    }

    protected function formFindModelObject($settingItem)
    {
        $model = $this->createModel($settingItem->model);

        // Prepare query and find model record
        $result = $model->getSettingsRecord();

        if (!$result) {
            return $model;
        }

        return $result;
    }

    protected function formValidate($model, $form)
    {
        if (!isset($form->config['rules']))
            return;

        return $this->validatePasses($form->getSaveData(),
            $form->config['rules'],
            array_get($form->config, 'validationMessages', []),
            array_get($form->config, 'validationAttributes', [])
        );
    }

    protected function checkDependencies($extension)
    {
        $feedback = null;
        $extensionManager = resolve(ExtensionManager::class);
        $required = $extensionManager->getDependencies($extension) ?: [];
        foreach ($required as $require) {
            $requireExtension = $extensionManager->findExtension($require);
            if (!$requireExtension)
                $feedback .= "Required extension [{$require}] was not found.\n";

            if ($extensionManager->isDisabled($require))
                $feedback .= "Required extension [{$require}] must be enabled to proceed.\n";
        }

        return $feedback;
    }

    protected function extensionHasMigrations($extension)
    {
        try {
            $extensionManager = resolve(ExtensionManager::class);

            return count($extensionManager->files($extension, 'database/migrations')) > 0;
        }
        catch (Exception $ex) {
            return false;
        }
    }
}
