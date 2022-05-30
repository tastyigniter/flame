<?php

namespace Igniter\System\DashboardWidgets;

use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Admin\Facades\AdminAuth;
use Igniter\System\Models\Activity;

/**
 * System activities dashboard widget.
 */
class Activities extends BaseDashboardWidget
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'activities';

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('activities/activities');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'label' => 'igniter::admin.dashboard.label_widget_title',
                'default' => 'igniter::admin.dashboard.text_recent_activity',
                'type' => 'text',
            ],
            'count' => [
                'label' => 'igniter::admin.dashboard.text_activities_count',
                'default' => 5,
                'type' => 'select',
                'options' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10],
            ],
        ];
    }

    protected function prepareVars()
    {
        $user = AdminAuth::getUser();
        $this->vars['activities'] = Activity::listRecent([
            'pageLimit' => $this->property('count'),
            'onlyUser' => $user,
        ])->get();
    }
}
