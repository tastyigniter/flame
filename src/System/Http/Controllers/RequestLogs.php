<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;
use Igniter\System\Models\RequestLog;

class RequestLogs extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\RequestLog::class,
            'title' => 'lang:igniter::system.request_logs.text_title',
            'emptyMessage' => 'lang:igniter::system.request_logs.text_empty',
            'defaultSort' => ['count', 'DESC'],
            'configFile' => 'requestlog',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::system.request_logs.text_form_name',
        'model' => \Igniter\System\Models\RequestLog::class,
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'request_logs',
        ],
        'delete' => [
            'redirect' => 'request_logs',
        ],
        'configFile' => 'requestlog',
    ];

    protected $requiredPermissions = 'Admin.SystemLogs';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('system_logs', 'system');
    }

    public function index_onEmptyLog()
    {
        RequestLog::truncate();

        flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Logs Emptied '));

        return $this->refreshList('list');
    }
}
