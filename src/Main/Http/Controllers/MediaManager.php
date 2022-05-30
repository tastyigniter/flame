<?php

namespace Igniter\Main\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;

class MediaManager extends \Igniter\Admin\Classes\AdminController
{
    protected $requiredPermissions = 'Admin.MediaManager';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('media_manager', 'tools');
    }

    public function index()
    {
        Template::setTitle(lang('igniter::main.media_manager.text_title'));
        Template::setHeading(lang('igniter::main.media_manager.text_heading'));
    }
}
