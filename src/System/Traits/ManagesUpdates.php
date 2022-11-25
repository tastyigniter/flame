<?php

namespace Igniter\System\Traits;

use Exception;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Classes\ComposerManager;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\UpdateManager;

trait ManagesUpdates
{
    public function search()
    {
        $json = [];

        if (($filter = input('filter')) && is_array($filter)) {
            $itemType = $filter['type'] ?? 'extension';
            $searchQuery = isset($filter['search']) ? strtolower($filter['search']) : '';

            try {
                $json = resolve(UpdateManager::class)->searchItems($itemType, $searchQuery);
            }
            catch (Exception $ex) {
                $json = $ex->getMessage();
            }
        }

        return $json;
    }

    public function onApplyRecommended()
    {
        $itemsCodes = post('install_items') ?? [];
        $items = collect(post('items') ?? [])->whereIn('name', $itemsCodes);
        if ($items->isEmpty())
            throw new ApplicationException(lang('igniter::system.updates.alert_no_items'));

        $this->validateItems();

        $response = resolve(UpdateManager::class)->requestApplyItems($items->all());
        $response = array_get($response, 'data', []);

        return [
            'steps' => $this->buildProcessSteps($response, $items),
        ];
    }

    public function onApplyItems()
    {
        $items = post('items') ?? [];
        if (!count($items))
            throw new ApplicationException(lang('igniter::system.updates.alert_no_items'));

        $this->validateItems();

        $response = resolve(UpdateManager::class)->requestApplyItems($items);
        $response = collect(array_get($response, 'data', []))
            ->whereIn('code', collect($items)->pluck('name')->all())
            ->all();

        return [
            'steps' => $this->buildProcessSteps($response, $items),
        ];
    }

    public function onApplyUpdate()
    {
        $items = post('items') ?? [];
        if (!count($items))
            throw new ApplicationException(lang('igniter::system.updates.alert_no_items'));

        $this->validateItems();

        $updates = resolve(UpdateManager::class)->requestUpdateList(input('check') == 'force');
        $response = array_get($updates, 'items');

        return [
            'steps' => $this->buildProcessSteps($response, $items),
        ];
    }

    public function onLoadRecommended()
    {
        $itemType = post('itemType');
        $items = (in_array($itemType, ['theme', 'extension']))
            ? resolve(UpdateManager::class)->listItems($itemType)
            : [];

        return $this->makePartial('updates/list_recommended', [
            'items' => $items,
            'itemType' => $itemType,
        ]);
    }

    public function onCheckUpdates()
    {
        $updateManager = resolve(UpdateManager::class);
        $updateManager->requestUpdateList(true);

        return $this->redirect($this->checkUrl);
    }

    public function onIgnoreUpdate()
    {
        $items = post('items');
        if (!$items || count($items) < 1)
            throw new ApplicationException(lang('igniter::system.updates.alert_item_to_ignore'));

        $updateManager = resolve(UpdateManager::class);

        $updateManager->ignoreUpdates($items);

        $updates = $updateManager->requestUpdateList(input('check') == 'force');

        return [
            '#updates' => $this->makePartial('updates/list', ['updates' => $updates]),
        ];
    }

    public function onApplyCarte()
    {
        $carteKey = post('carte_key');
        if (!strlen($carteKey))
            throw new ApplicationException(lang('igniter::system.updates.alert_no_carte_key'));

        $response = resolve(UpdateManager::class)->applySiteDetail($carteKey);

        return [
            '#carte-details' => $this->makePartial('updates/carte_info', ['carteInfo' => $response]),
        ];
    }

    public function onProcessItems()
    {
        return $this->processInstallOrUpdate();
    }

    //
    //
    //

    protected function initUpdate($itemType)
    {
        resolve(ComposerManager::class)->loadRepositoryAndAuthConfig();

        $this->prepareAssets();

        $updateManager = resolve(UpdateManager::class);

        $this->vars['itemType'] = $itemType;
        $this->vars['carteInfo'] = $updateManager->getSiteDetail();
        $this->vars['installedItems'] = $updateManager->getInstalledItems();
    }

    protected function prepareAssets()
    {
        $this->addJs('vendor/mustache.min.js', 'mustache-js');
        $this->addJs('vendor/typeahead.js', 'typeahead-js');
        $this->addJs('updates.js', 'updates-js');
        $this->addJs('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
    }

    protected function buildProcessSteps($response, $params = [])
    {
        $processSteps = [];
        foreach (['install', 'complete'] as $step) {
            if ($step == 'complete') {
                $processSteps[$step][] = [
                    'items' => $response,
                    'process' => $step,
                    'label' => lang('igniter::system.updates.progress_complete'),
                    'success' => lang('igniter::system.updates.progress_success'),
                ];
            }
            else {
                $processSteps[$step][] = [
                    'items' => $response,
                    'process' => 'updateComposer',
                    'label' => lang('igniter::system.updates.progress_composer'),
                    'success' => lang('igniter::system.updates.progress_composer_success'),
                ];

                if ($coreUpdate = collect($response)->firstWhere('type', 'core')) {
                    $processSteps[$step][] = array_merge($coreUpdate, [
                        'action' => 'update',
                        'process' => "{$step}Core",
                        'label' => lang('igniter::system.updates.progress_core'),
                        'success' => lang('igniter::system.updates.progress_core_success'),
                    ]);
                }

                $addonUpdates = collect($response)->where('type', '!=', 'core');
                if ($addonUpdates->isNotEmpty()) {
                    $processSteps[$step][] = [
                        'items' => $addonUpdates->all(),
                        'process' => "{$step}Addon",
                        'label' => lang('igniter::system.updates.progress_addons'),
                        'success' => lang('igniter::system.updates.progress_addons_success'),
                    ];
                }
            }
        }

        return $processSteps;
    }

    protected function processInstallOrUpdate()
    {
        $json = [];

        $this->validateProcess();

        $meta = post('meta');

        $composerManager = resolve(ComposerManager::class);

        $result = match ($meta['process']) {
            'updateComposer' => $composerManager->require(['composer/composer']),
            'installCore' => $composerManager->requireCore($meta['version']),
            'installAddon' => $composerManager->require(collect($meta['items'])->map(function ($item) {
                return $item['package'].':'.$item['version'];
            })->all()),
            'complete' => $this->completeProcess($meta['items']),
            default => false,
        };

        if ($result) $json['result'] = 'success';

        return $json;
    }

    protected function completeProcess($items)
    {
        if (!count($items))
            return false;

        foreach ($items as $item) {
            if ($item['type'] == 'core') {
                $updateManager = resolve(UpdateManager::class);
                $updateManager->update();
                $updateManager->setCoreVersion($item['version'], $item['hash']);

                break;
            }

            switch ($item['type']) {
                case 'extension':
                    resolve(ExtensionManager::class)->installExtension($item['code'], $item['version']);
                    break;
                case 'theme':
                    resolve(ThemeManager::class)->installTheme($item['code'], $item['version']);
                    break;
            }
        }

        resolve(UpdateManager::class)->requestUpdateList(true);

        return true;
    }

    protected function getActionFromItems($code, $itemNames)
    {
        foreach ($itemNames as $itemName) {
            if ($code == $itemName['name'])
                return $itemName['action'];
        }
    }

    protected function validateItems()
    {
        return $this->validate(post(), [
            'items.*.name' => ['required'],
            'items.*.type' => ['required', 'in:core,extension,theme,language'],
            'items.*.ver' => ['required'],
            'items.*.action' => ['required', 'in:install,update'],
        ], [], [
            'items.*.name' => lang('igniter::system.updates.label_meta_code'),
            'items.*.type' => lang('igniter::system.updates.label_meta_type'),
            'items.*.ver' => lang('igniter::system.updates.label_meta_version'),
            'items.*.action' => lang('igniter::system.updates.label_meta_action'),
        ]);
    }

    protected function validateProcess()
    {
        $rules = [
            'meta.code' => ['sometimes', 'required'],
            'meta.type' => ['sometimes', 'required', 'in:core,extension,theme,language'],
            'meta.version' => ['sometimes', 'required'],
            'meta.hash' => ['sometimes', 'required'],
            'meta.description' => ['sometimes'],
            'meta.action' => ['sometimes', 'required', 'in:install,update'],
        ];

        $attributes = [
            'meta.code' => lang('igniter::system.updates.label_meta_code'),
            'meta.type' => lang('igniter::system.updates.label_meta_type'),
            'meta.version' => lang('igniter::system.updates.label_meta_version'),
            'meta.hash' => lang('igniter::system.updates.label_meta_hash'),
            'meta.description' => lang('igniter::system.updates.label_meta_description'),
            'meta.action' => lang('igniter::system.updates.label_meta_action'),
        ];

        $rules['step'] = ['required', 'in:install,complete'];
        $rules['meta.items'] = ['sometimes', 'required', 'array'];

        $attributes['step'] = lang('igniter::system.updates.label_meta_step');
        $attributes['meta.items'] = lang('igniter::system.updates.label_meta_items');

        return $this->validate(post(), $rules, [], $attributes);
    }
}
