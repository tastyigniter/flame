<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\ActivityTypes\StatusUpdated;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Models\Order;
use Igniter\Admin\Models\Status;
use Igniter\Flame\Exception\ApplicationException;

class Orders extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
        \Igniter\Admin\Http\Actions\LocationAwareController::class,
        \Igniter\Admin\Http\Actions\AssigneeController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\Order::class,
            'title' => 'lang:igniter::admin.orders.text_title',
            'emptyMessage' => 'lang:igniter::admin.orders.text_empty',
            'defaultSort' => ['order_id', 'DESC'],
            'configFile' => 'order',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.orders.text_form_name',
        'model' => \Igniter\Admin\Models\Order::class,
        'request' => \Igniter\Admin\Requests\Order::class,
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'orders/edit/{order_id}',
            'redirectClose' => 'orders',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'orders',
        ],
        'delete' => [
            'redirect' => 'orders',
        ],
        'configFile' => 'order',
    ];

    protected $requiredPermissions = [
        'Admin.Orders',
        'Admin.AssignOrders',
        'Admin.DeleteOrders',
    ];

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('orders', 'sales');
    }

    public function index()
    {
        $this->asExtension('ListController')->index();

        $this->vars['statusesOptions'] = \Igniter\Admin\Models\Status::getDropdownOptionsForOrder();
    }

    public function index_onDelete()
    {
        if (!$this->getUser()->hasPermission('Admin.DeleteOrders'))
            throw new ApplicationException(lang('igniter::admin.alert_user_restricted'));

        return $this->asExtension(\Igniter\Admin\Http\Actions\ListController::class)->index_onDelete();
    }

    public function index_onUpdateStatus()
    {
        $model = Order::find((int)post('recordId'));
        $status = Status::find((int)post('statusId'));
        if (!$model || !$status)
            return;

        if ($record = $model->addStatusHistory($status))
            StatusUpdated::log($record, $this->getUser());

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::admin.statuses.text_form_name').' updated'))->now();

        return $this->redirectBack();
    }

    public function edit_onDelete($context, $recordId)
    {
        if (!$this->getUser()->hasPermission('Admin.DeleteOrders'))
            throw new ApplicationException(lang('igniter::admin.alert_user_restricted'));

        return $this->asExtension(\Igniter\Admin\Http\Actions\FormController::class)->edit_onDelete($context, $recordId);
    }

    public function invoice($context, $recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        if (!$model->hasInvoice())
            throw new ApplicationException(lang('igniter::admin.orders.alert_invoice_not_generated'));

        $this->vars['model'] = $model;

        $this->suppressLayout = true;
    }

    public function formExtendQuery($query)
    {
        $query->with([
            'status_history' => function ($q) {
                $q->orderBy('created_at', 'desc');
            },
        ]);
    }
}
