<?php

namespace Igniter\System\Http\Controllers;

use Exception;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Main\Models\Theme;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Models\Extension;
use Igniter\System\Traits\ManagesUpdates;

class Updates extends \Igniter\Admin\Classes\AdminController
{
    use ManagesUpdates;

    public $checkUrl = 'updates';

    public $browseUrl = 'updates/browse';

    protected $requiredPermissions = 'Site.Updates';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('updates', 'system');
    }

    public function index()
    {
        Extension::syncAll();
        Theme::syncAll();

        $pageTitle = lang('igniter::system.updates.text_title');
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        Template::setButton(lang('igniter::system.updates.button_check'), ['class' => 'btn btn-success', 'data-request' => 'onCheckUpdates']);
        Template::setButton(lang('igniter::system.updates.button_carte'), ['class' => 'btn btn-default pull-right', 'role' => 'button', 'data-bs-target' => '#carte-modal', 'data-bs-toggle' => 'modal']);

        Template::setButton(sprintf(lang('igniter::system.version'), params('ti_version')), [
            'class' => 'btn disabled text-muted pull-right', 'role' => 'button',
        ]);

        $this->prepareAssets();

        try {
            $updateManager = resolve(UpdateManager::class);
            $this->vars['carteInfo'] = $updateManager->getSiteDetail();
            $this->vars['updates'] = $updates = $updateManager->requestUpdateList();

            $lastChecked = isset($updates['last_check'])
                ? time_elapsed($updates['last_check'])
                : lang('igniter::admin.text_never');

            Template::setButton(sprintf(lang('igniter::system.updates.text_last_checked'), $lastChecked), [
                'class' => 'btn disabled text-muted pull-right', 'role' => 'button',
            ]);

            if (!empty($updates['items']) || !empty($updates['ignoredItems'])) {
                Template::setButton(lang('igniter::system.updates.button_update'), [
                    'class' => 'btn btn-primary pull-left mr-2 ml-0',
                    'id' => 'apply-updates', 'role' => 'button',
                ]);
            }
        }
        catch (Exception $ex) {
            flash()->warning($ex->getMessage())->now();
        }
    }
}
