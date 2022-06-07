<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\LanguageManager;
use Igniter\System\Models\Language;
use Igniter\System\Traits\ManagesUpdates;
use Igniter\System\Traits\SessionMaker;

class Languages extends \Igniter\Admin\Classes\AdminController
{
    use SessionMaker;
    use ManagesUpdates;

    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\Language::class,
            'title' => 'lang:igniter::system.languages.text_title',
            'emptyMessage' => 'lang:igniter::system.languages.text_empty',
            'defaultSort' => ['language_id', 'DESC'],
            'configFile' => 'language',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::system.languages.text_form_name',
        'model' => \Igniter\System\Models\Language::class,
        'request' => \Igniter\System\Requests\Language::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'languages/edit/{language_id}',
            'redirectClose' => 'languages',
            'redirectNew' => 'languages/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'languages/edit/{language_id}',
            'redirectClose' => 'languages',
            'redirectNew' => 'languages/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'languages',
        ],
        'delete' => [
            'redirect' => 'languages',
        ],
        'configFile' => 'language',
    ];

    protected $requiredPermissions = 'Site.Languages';

    protected $localeFiles;

    protected $totalStrings;

    protected $totalTranslated;

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('languages', 'localisation');
    }

    public function index()
    {
        Language::applySupportedLanguages();

        $this->initUpdate('language');

        $this->asExtension('ListController')->index();
    }

    public function search()
    {
        $filter = input('filter');
        if (!$filter || !is_array($filter) || !isset($filter['search']) || !strlen($filter['search']))
            return [];

        return resolve(LanguageManager::class)->searchLanguages($filter['search']);
    }

    public function edit($context = null, $recordId = null)
    {
        $this->addJs('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
        $this->addJs('formwidgets/translationseditor.js', 'translationseditor-js');

        $this->prepareAssets();

        Template::setButton(lang('igniter::system.languages.button_check'), ['class' => 'btn btn-success pull-right', 'data-toggle' => 'record-editor', 'data-handler' => 'onCheckUpdates']);

        $this->asExtension('FormController')->edit($context, $recordId);
    }

    public function edit_onSubmitFilter($context = null, $recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        $this->asExtension('FormController')->initForm($model, $context);

        $file = post('Language._file');
        $this->setFilterValue('file', (!strlen($file) || strpos($file, '::') == false) ? null : $file);

        $term = post('Language._search');
        $this->setFilterValue('search', (!strlen($term) || !is_string($term)) ? null : $term);

        $stringFilter = post('Language._string_filter');
        $this->setFilterValue('string_filter', (!strlen($stringFilter) || !is_string($stringFilter)) ? null : $stringFilter);

        return $this->asExtension('FormController')->makeRedirect('edit', $model);
    }

    public function edit_onCheckUpdates($context = null, $recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        $response = resolve(LanguageManager::class)->applyLanguagePack($model->code, $model->version);

        $title = $response
            ? lang('igniter::system.languages.text_title_update_available')
            : lang('igniter::system.languages.text_title_no_update_available');

        $message = $response
            ? lang('igniter::system.languages.text_update_available')
            : lang('igniter::system.languages.text_no_update_available');

        return $this->makePartial('updates', [
            'language' => (object)$response,
            'title' => $title,
            'message' => sprintf($message, $model->name),
        ]);
    }

    public function onApplyItems()
    {
        $items = post('items') ?? [];
        if (!count($items))
            throw new ApplicationException(lang('igniter::system.updates.alert_no_items'));

        $this->validateItems();

        $response = resolve(LanguageManager::class)->applyLanguagePack($items[0]['name']);

        return [
            'steps' => $this->buildProcessSteps([$response], $items),
        ];
    }

    public function edit_onApplyUpdate($context = null, $recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        $response = resolve(LanguageManager::class)->applyLanguagePack($model->code);

        return [
            'steps' => $this->buildProcessSteps([$response], [[
                'name' => $model->code,
                'action' => 'update',
            ]]),
        ];
    }

    public function onProcessItems()
    {
        $json = [];

        $this->validateProcess();

        $meta = post('meta');

        $languageManager = resolve(LanguageManager::class);

        $processMeta = $meta['process'];
        switch ($processMeta) {
            case 'downloadLanguage':
                $result = $languageManager->downloadPack($meta);
                if ($result) $json['result'] = 'success';
                break;
            case 'extractLanguage':
                $response = $languageManager->extractPack($meta);
                if ($response) $json['result'] = 'success';
                break;
            case 'complete':
                $response = $languageManager->installPack($meta['items'][0] ?? []);
                if ($response) $json['result'] = 'success';
                break;
        }

        return $json;
    }

    public function formExtendFields(Form $form, $fields)
    {
        if ($form->getContext() !== 'edit')
            return;

        $fileField = $form->getField('_file');
        $searchField = $form->getField('_search');
        $stringFilterField = $form->getField('_string_filter');
        $field = $form->getField('translations');

        $fileField->value = $this->getFilterValue('file');
        $searchField->value = $this->getFilterValue('search');
        $stringFilterField->value = $this->getFilterValue('string_filter', 'all');
        $field->value = $this->getFilterValue('search');

        if (is_null($this->localeFiles))
            $this->localeFiles = resolve(LanguageManager::class)->listLocaleFiles('en');

        $fileField->options = $this->prepareNamespaces();
        $field->options = post($field->getName()) ?: $this->prepareTranslations($form->model);

        if ($form->model->version) {
            Template::setButton(sprintf(lang('igniter::system.languages.text_current_build'), $form->model->version), [
                'class' => 'btn disabled text-muted pull-right', 'role' => 'button',
            ]);
        }

        $this->vars['totalStrings'] = $this->totalStrings;
        $this->vars['totalTranslated'] = $this->totalTranslated;
        $this->vars['translatedProgress'] = $this->totalStrings ? round(($this->totalTranslated * 100) / $this->totalStrings, 2) : 0;
    }

    protected function getFilterValue($key, $default = null)
    {
        return $this->getSession('translation_'.$key, $default);
    }

    protected function setFilterValue($key, $value)
    {
        $this->putSession('translation_'.$key, trim($value));
    }

    protected function prepareNamespaces()
    {
        $result = [];

        $extensionManager = resolve(ExtensionManager::class);

        foreach ($this->localeFiles as $file) {
            $name = sprintf('%s::%s', $file['namespace'], $file['group']);

            if (!array_get($file, 'system', false)
                && ($extension = $extensionManager->findExtension($file['namespace']))) {
                $result[$name] = array_get($extension->extensionMeta(), 'name').' - '.$name;
            }
            else {
                $result[$name] = ucfirst($file['namespace']).' - '.$name;
            }
        }

        return $result;
    }

    protected function prepareTranslations($model)
    {
        $this->totalStrings = 0;
        $this->totalTranslated = 0;
        $stringFilter = $this->getFilterValue('string_filter');
        $files = collect($this->localeFiles);

        $file = $this->getFilterValue('file');
        if (strlen($file) && strpos($file, '::')) {
            [$namespace, $group] = explode('::', $file);
            $files = $files->where('group', $group)->where('namespace', $namespace);
        }

        $manager = resolve(LanguageManager::class);

        $result = [];
        $files->each(function ($file) use ($manager, $model, &$result, $stringFilter) {
            $sourceLines = $model->getLines('en', $file['group'], $file['namespace']);
            $translationLines = $model->getTranslations($file['group'], $file['namespace']);

            $this->totalStrings += count($sourceLines);
            $this->totalTranslated += count($translationLines);

            $translations = $manager->listTranslations($sourceLines, $translationLines, [
                'file' => $file,
                'stringFilter' => $stringFilter,
            ]);

            $result = array_merge($result, $translations);
        });

        $term = $this->getFilterValue('search');
        $result = $manager->searchTranslations($result, $term);

        return $manager->paginateTranslations($result);
    }
}
