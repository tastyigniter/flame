<?php

namespace Igniter\Admin\Http\Controllers;

use Exception;
use Igniter\Admin\ActivityTypes\StatusUpdated;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Models\Reservation;
use Igniter\Admin\Models\Status;
use Igniter\Flame\Exception\ApplicationException;

class Reservations extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\CalendarController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
        \Igniter\Admin\Http\Actions\AssigneeController::class,
        \Igniter\Admin\Http\Actions\LocationAwareController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\Reservation::class,
            'title' => 'lang:igniter::admin.reservations.text_title',
            'emptyMessage' => 'lang:igniter::admin.reservations.text_empty',
            'defaultSort' => ['reservation_id', 'DESC'],
            'configFile' => 'reservation',
        ],
    ];

    public $calendarConfig = [
        'calender' => [
            'title' => 'lang:igniter::admin.reservations.text_title',
            'emptyMessage' => 'lang:igniter::admin.reservations.text_no_booking',
            'popoverPartial' => 'reservations/calendar_popover',
            'configFile' => 'reservation',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.reservations.text_form_name',
        'model' => \Igniter\Admin\Models\Reservation::class,
        'request' => \Igniter\Admin\Requests\Reservation::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'reservations/edit/{reservation_id}',
            'redirectClose' => 'reservations',
            'redirectNew' => 'reservations/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'reservations/edit/{reservation_id}',
            'redirectClose' => 'reservations',
            'redirectNew' => 'reservations/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'reservations',
        ],
        'delete' => [
            'redirect' => 'reservations',
        ],
        'configFile' => 'reservation',
    ];

    protected $requiredPermissions = [
        'Admin.Reservations',
        'Admin.AssignReservations',
        'Admin.DeleteReservations',
    ];

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('reservations', 'sales');
    }

    public function index()
    {
        $this->asExtension('ListController')->index();

        $this->vars['statusesOptions'] = \Igniter\Admin\Models\Status::getDropdownOptionsForReservation();
    }

    public function index_onDelete()
    {
        if (!$this->getUser()->hasPermission('Admin.DeleteReservations'))
            throw new ApplicationException(lang('igniter::admin.alert_user_restricted'));

        return $this->asExtension(\Igniter\Admin\Http\Actions\ListController::class)->index_onDelete();
    }

    public function index_onUpdateStatus()
    {
        $model = Reservation::find((int)post('recordId'));
        $status = Status::find((int)post('statusId'));
        if (!$model || !$status)
            return;

        if ($record = $model->addStatusHistory($status))
            StatusUpdated::log($record, $this->getUser());

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::admin.statuses.text_form_name').' updated'))->now();

        return $this->redirectBack();
    }

    public function edit_onDelete()
    {
        if (!$this->getUser()->hasPermission('Admin.DeleteReservations'))
            throw new ApplicationException(lang('igniter::admin.alert_user_restricted'));

        return $this->asExtension(\Igniter\Admin\Http\Actions\FormController::class)->edit_onDelete();
    }

    public function calendarGenerateEvents($startAt, $endAt)
    {
        return Reservation::listCalendarEvents(
            $startAt, $endAt, $this->getLocationId()
        );
    }

    public function calendarUpdateEvent($eventId, $startAt, $endAt)
    {
        if (!$reservation = Reservation::find($eventId))
            throw new Exception(lang('igniter::admin.reservations.alert_no_reservation_found'));

        $startAt = make_carbon($startAt);
        $endAt = make_carbon($endAt);

        $reservation->duration = $startAt->diffInMinutes($endAt);
        $reservation->reserve_date = $startAt->toDateString();
        $reservation->reserve_time = $startAt->toTimeString();

        $reservation->save();
    }

    public function formExtendQuery($query)
    {
        $query->with([
            'status_history' => function ($q) {
                $q->orderBy('created_at', 'desc');
            },
            'status_history.staff',
            'status_history.status',
        ]);
    }
}
