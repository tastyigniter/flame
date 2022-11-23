<?php

namespace Igniter\Main\Http\Controllers;

use Exception;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme;
use Igniter\System\Facades\Assets;
use Igniter\System\Helpers\CacheHelper;
use Igniter\System\Libraries\Assets as AssetsManager;
use Igniter\System\Traits\ManagesUpdates;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class Themes extends \Igniter\Admin\Classes\AdminController
{
    use ManagesUpdates;

    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Main\Models\Theme::class,
            'title' => 'lang:igniter::system.themes.text_title',
            'emptyMessage' => 'lang:igniter::system.themes.text_empty',
            'defaultSort' => ['theme_id', 'DESC'],
            'configFile' => 'theme',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::system.themes.text_form_name',
        'model' => \Igniter\Main\Models\Theme::class,
        'request' => \Igniter\Main\Requests\Theme::class,
        'edit' => [
            'title' => 'igniter::system.themes.text_edit_title',
            'redirect' => 'themes/edit/{code}',
            'redirectClose' => 'themes',
        ],
        'source' => [
            'title' => 'igniter::system.themes.text_source_title',
            'redirect' => 'themes/source/{code}',
            'redirectClose' => 'themes',
        ],
        'delete' => [
            'redirect' => 'themes',
        ],
        'configFile' => 'theme',
    ];

    protected $requiredPermissions = 'Site.Themes';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('themes', 'design');
    }

    public function index()
    {
        Theme::syncAll();

        $this->initUpdate('theme');

        $this->asExtension('ListController')->index();
    }

    public function edit($context, $themeCode = null)
    {
        if (!resolve(ThemeManager::class)->isActive($themeCode)) {
            flash()->error(lang('igniter::system.themes.alert_customize_not_active'));

            return $this->redirect('themes');
        }

        if (resolve(ThemeManager::class)->isLocked($themeCode)) {
            Template::setButton(lang('igniter::system.themes.button_child'), [
                'class' => 'btn btn-default pull-right',
                'data-request' => 'onCreateChild',
            ]);
        }

        Template::setButton(lang('igniter::system.themes.button_source'), [
            'class' => 'btn btn-default pull-right mr-3',
            'href' => admin_url('themes/source/'.$themeCode),
        ]);

        $this->asExtension('FormController')->edit($context, $themeCode);
    }

    public function source($context, $themeCode = null)
    {
        if (resolve(ThemeManager::class)->isLocked($themeCode)) {
            Template::setButton(lang('igniter::system.themes.button_child'), [
                'class' => 'btn btn-default pull-right',
                'data-request' => 'onCreateChild',
            ]);
        }

        $theme = resolve(ThemeManager::class)->findTheme($themeCode);
        if ($theme && $theme->hasCustomData()) {
            Template::setButton(lang('igniter::system.themes.button_customize'), [
                'class' => 'btn btn-default pull-right mr-3',
                'href' => admin_url('themes/edit/'.$themeCode),
            ]);
        }

        $this->asExtension('FormController')->edit($context, $themeCode);

        return $this->makeView('edit');
    }

    public function delete($context, $themeCode = null)
    {
        try {
            $pageTitle = lang('igniter::system.themes.text_delete_title');
            Template::setTitle($pageTitle);
            Template::setHeading($pageTitle);

            $themeManager = resolve(ThemeManager::class);
            $theme = $themeManager->findTheme($themeCode);
            $model = Theme::whereCode($themeCode)->first();
            $activeThemeCode = params()->get('default_themes.main');

            // Theme must be disabled before it can be deleted
            if ($model && $model->code == $activeThemeCode) {
                flash()->warning(sprintf(
                    lang('igniter::admin.alert_error_nothing'),
                    lang('igniter::admin.text_deleted').lang('igniter::system.themes.text_theme_is_active')
                ));

                return $this->redirectBack();
            }

            // Theme not found in filesystem
            // so delete from database
            if (!$theme) {
                Theme::deleteTheme($themeCode, true);
                flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Theme deleted '));

                return $this->redirectBack();
            }

            // Lets display a delete confirmation screen
            // with list of files to be deleted
            $this->vars['themeModel'] = $model;
            $this->vars['themeObj'] = $theme;
            $this->vars['themeData'] = $model->data;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function index_onSetDefault()
    {
        $themeName = post('code');
        if ($theme = Theme::activateTheme($themeName)) {
            CacheHelper::clearView();

            flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Theme ['.$theme->name.'] set as default '));
        }

        return $this->redirectBack();
    }

    public function source_onSave($context, $themeCode = null)
    {
        $formController = $this->asExtension('FormController');
        $model = $this->formFindModelObject($themeCode);
        $formController->initForm($model, $context);

        $this->widgets['formTemplate']->onSaveSource();

        flash()->success(
            sprintf(lang('igniter::admin.form.edit_success'), lang('lang:igniter::system.themes.text_form_name'))
        );

        if ($redirect = $formController->makeRedirect($context, $model)) {
            return $redirect;
        }
    }

    public function onCreateChild($context, $themeCode = null)
    {
        $manager = resolve(ThemeManager::class);

        $model = $this->formFindModelObject($themeCode);

        $childTheme = $manager->createChildTheme($model);

        Theme::syncAll();
        Theme::activateTheme($childTheme->code);

        flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Child theme ['.$childTheme->name.'] created '));

        return $this->redirect('themes/source/'.$childTheme->code);
    }

    public function delete_onDelete($context = null, $themeCode = null)
    {
        if (Theme::deleteTheme($themeCode, post('delete_data', 1) == 1)) {
            flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Theme deleted '));
        }
        else {
            flash()->danger(lang('igniter::admin.alert_error_try_again'));
        }

        return $this->redirect('themes');
    }

    public function listOverrideColumnValue($record, $column, $alias = null)
    {
        if ($column->type != 'button' || $column->columnName != 'default')
            return null;

        $attributes = $column->attributes;

        $column->iconCssClass = 'fa fa-star-o';
        if ($record->getTheme() && $record->getTheme()->isActive()) {
            $column->iconCssClass = 'fa fa-star';
            $attributes['title'] = 'lang:igniter::system.themes.text_is_default';
            $attributes['data-request'] = null;
        }

        return $attributes;
    }

    public function formExtendConfig(&$formConfig)
    {
        $formConfig['data'] = $formConfig['model']->toArray();

        if ($formConfig['context'] != 'source') {
            $formConfig['tabs']['fields'] = $formConfig['model']->getFieldsConfig();
            $formConfig['data'] = array_merge($formConfig['model']->getFieldValues(), $formConfig['data']);
            $formConfig['arrayName'] .= '[data]';

            return;
        }

        $formConfig['arrayName'] .= '[source]';
    }

    public function formFindModelObject($recordId)
    {
        if (!strlen($recordId)) {
            throw new Exception(lang('igniter::admin.form.missing_id'));
        }

        $model = $this->formCreateModelObject();

        // Prepare query and find model record
        $query = $model->newQuery();
        $result = $query->where('code', $recordId)->first();

        if (!$result) {
            throw new Exception(sprintf(lang('igniter::admin.form.not_found'), $recordId));
        }

        return $result;
    }

    public function formAfterSave($model)
    {
        if ($this->widgets['form']->context != 'source') {
            $this->buildAssetsBundle($model);
        }
    }

    protected function buildAssetsBundle($model)
    {
        if (!$model->getFieldsConfig())
            return;

        if (!config('igniter.system.publishThemeAssetsBundle', true))
            return;

        $loaded = false;
        $theme = $model->getTheme();
        $file = '/_meta/assets.json';

        if (File::exists($path = $theme->path.$file)) {
            Assets::addFromManifest($theme->publicPath.$file);
            $loaded = true;
        }

        if ($theme->hasParent() && File::exists($path = $theme->getParent()->path.$file)) {
            Assets::addFromManifest($theme->getParent()->publicPath.$file);
            $loaded = true;
        }

        if (!$loaded)
            return;

        Event::listen('assets.combiner.beforePrepare', function (AssetsManager $combiner, $assets) use ($theme) {
            resolve(ThemeManager::class)->applyAssetVariablesOnCombinerFilters(
                array_flatten($combiner->getFilters()), $theme
            );
        });

        try {
            Artisan::call('igniter:util', ['name' => 'compile scss']);
            Artisan::call('igniter:util', ['name' => 'compile js']);
        }
        catch (Exception $ex) {
            Log::error($ex);
            flash()->error('Building assets bundle error: '.$ex->getMessage())->important();
        }
    }

    public function getTemplateValue($name, $default = null)
    {
        $themeCode = $this->params[0] ?? 'default';
        $cacheKey = $themeCode.'-selected-'.$name;

        return $this->getSession($cacheKey, $default);
    }

    public function setTemplateValue($name, $value)
    {
        $themeCode = $this->params[0] ?? 'default';
        $cacheKey = $themeCode.'-selected-'.$name;
        $this->putSession($cacheKey, $value);
    }
}
