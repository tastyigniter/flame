<?php

namespace Igniter\Admin\DashboardWidgets;

use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Admin\Models\Order;
use Igniter\Admin\Models\Reservation;
use Igniter\Admin\Traits\HasChartDatasets;
use Igniter\Main\Models\Customer;

/**
 * Charts dashboard widget.
 */
class Charts extends BaseDashboardWidget
{
    use HasChartDatasets;

    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'charts';

    protected $datasetOptions = [
        'label' => null,
        'data' => [],
        'fill' => true,
        'backgroundColor' => null,
        'borderColor' => null,
    ];

    public $contextDefinitions;

    public function initialize()
    {
        $this->setProperty('rangeFormat', 'MMMM D, YYYY');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'label' => 'igniter::admin.dashboard.label_widget_title',
                'default' => 'igniter::admin.dashboard.text_reports_chart',
            ],
        ];
    }

    /**
     * Renders the widget.
     */
    public function render()
    {
        return $this->makePartial('charts/charts');
    }

    public function listContext()
    {
        $this->contextDefinitions = [
            'customer' => [
                'label' => 'lang:igniter::admin.dashboard.charts.text_customers',
                'color' => '#4DB6AC',
                'model' => Customer::class,
                'column' => 'created_at',
            ],
            'order' => [
                'label' => 'lang:igniter::admin.dashboard.charts.text_orders',
                'color' => '#64B5F6',
                'model' => Order::class,
                'column' => 'order_date',
            ],
            'reservation' => [
                'label' => 'lang:igniter::admin.dashboard.charts.text_reservations',
                'color' => '#BA68C8',
                'model' => Reservation::class,
                'column' => 'reserve_date',
            ],
        ];

        $this->fireSystemEvent('admin.charts.extendDatasets');

        return $this->contextDefinitions;
    }

    protected function getDatasets($start, $end)
    {
        $result = [];
        foreach ($this->listContext() as $context => $config) {
            $result[] = $this->makeDataset($config, $start, $end);
        }

        return $result;
    }
}
