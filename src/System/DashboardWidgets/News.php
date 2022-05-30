<?php

namespace Igniter\System\DashboardWidgets;

use DOMDocument;
use Igniter\Admin\Classes\BaseDashboardWidget;

/**
 * TastyIgniter news dashboard widget.
 */
class News extends BaseDashboardWidget
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'news';

    public $newsRss = 'https://feeds.feedburner.com/Tastyigniter';

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('news/news');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'label' => 'igniter::admin.dashboard.label_widget_title',
                'default' => 'igniter::admin.dashboard.text_news',
            ],
            'newsCount' => [
                'label' => 'igniter::admin.dashboard.text_news_count',
                'default' => 5,
                'type' => 'select',
                'options' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10],
            ],
        ];
    }

    protected function prepareVars()
    {
        $this->vars['newsRss'] = $this->createRssDocument();
    }

    public function createRssDocument()
    {
        return class_exists('DOMDocument', false) ? new DOMDocument() : null;
    }
}
