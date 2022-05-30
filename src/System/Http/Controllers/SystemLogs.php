<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\LogViewer;

class SystemLogs extends \Igniter\Admin\Classes\AdminController
{
    protected $requiredPermissions = 'Admin.SystemLogs';

    protected $logFile = 'system.';

    public function index()
    {
        AdminMenu::setContext('system_logs', 'system');

        Template::setTitle(lang('igniter::system.system_logs.text_title'));
        Template::setHeading(lang('igniter::system.system_logs.text_title'));
        Template::setButton(lang('igniter::admin.button_refresh'), [
            'class' => 'btn btn-primary',
            'href' => 'system_logs',
        ]);
        Template::setButton(lang('igniter::system.system_logs.button_empty'), [
            'class' => 'btn btn-danger',
            'data-request-form' => '#list-form',
            'data-request' => 'onEmptyLog',
        ]);
        Template::setButton(lang('igniter::system.system_logs.button_request_logs'), [
            'class' => 'btn btn-default',
            'href' => 'request_logs',
        ]);

        $logFile = $this->getLogsFile();

        $logs = [];
        if (File::exists($logFile)) {
            LogViewer::setFile($logFile);
            $logs = LogViewer::all() ?? [];
        }

        $this->vars['logs'] = $logs;
    }

    public function index_onEmptyLog()
    {
        $logFile = $this->getLogsFile();
        if (File::exists($logFile) && File::isWritable($logFile)) {
            File::put($logFile, '');

            flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Logs Emptied '));
        }

        return $this->redirectBack();
    }

    /**
     * Get the path to the logs file
     *
     * @return string
     */
    protected function getLogsFile()
    {
        // default daily rotating logs (Laravel 5.0)
        $path = storage_path().'/logs/laravel-'.date('Y-m-d').'.log';

        // single file logs
        if (!file_exists($path)) {
            $path = storage_path().'/logs/laravel.log';
        }

        return $path;
    }
}
