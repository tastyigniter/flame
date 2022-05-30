<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;

class Activities extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\Activity::class,
            'title' => 'lang:igniter::system.activities.text_title',
            'emptyMessage' => 'lang:igniter::system.activities.text_empty',
            'defaultSort' => ['updated_at', 'DESC'],
            'configFile' => 'activity',
        ],
    ];

    protected $requiredPermissions = 'Admin.Activities';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('activities', 'system');
    }

    public function listExtendQuery($query)
    {
        $query->listRecent([
            'onlyUser' => $this->currentUser,
        ]);
    }
}
