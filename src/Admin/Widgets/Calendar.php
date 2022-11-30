<?php

namespace Igniter\Admin\Widgets;

use Carbon\Carbon;
use Exception;
use Igniter\Admin\Classes\BaseWidget;
use Illuminate\Support\Facades\Request;

class Calendar extends BaseWidget
{
    /**
     * @var string Defines the width-to-height aspect ratio of the calendar.
     */
    public $aspectRatio = 2;

    /**
     * @var string Determines whether the events on the calendar can be modified.
     */
    public $editable = true;

    /**
     * @var string Defines the number of events displayed on a day
     */
    public $eventLimit = 5;

    /**
     * @var string Defines initial date displayed when the calendar first loads.
     */
    public $defaultDate;

    /**
     * @var string Defines the event popover partial.
     */
    public $popoverPartial;

    public function initialize()
    {
        $this->fillFromConfig([
            'aspectRatio',
            'editable',
            'eventLimit',
            'defaultDate',
            'popoverPartial',
        ]);
    }

    public function loadAssets()
    {
        $this->addJs('js/vendor.datetime.js', 'vendor-datetime-js');
        $this->addCss('formwidgets/datepicker.css', 'datepicker-css');

        $this->addJs('vendor/mustache.min.js', 'mustache-js');

        $this->addJs('vendor/fullcalendar/main.min.js', 'fullcalendar-js');
        $this->addJs('js/locales/fullcalendar/locales-all.min.js', 'fullcalendar-locales-js');
        $this->addCss('vendor/fullcalendar/main.min.css', 'fullcalendar-css');

        $this->addJs('calendar.js', 'calendar-js');
        $this->addCss('calendar.css', 'calendar-css');
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('calendar/calendar');
    }

    public function prepareVars()
    {
        $this->vars['aspectRatio'] = $this->aspectRatio;
        $this->vars['editable'] = $this->editable;
        $this->vars['defaultDate'] = $this->defaultDate ?: Carbon::now()->toDateString();
        $this->vars['eventLimit'] = $this->eventLimit;
    }

    public function onGenerateEvents()
    {
        $startAt = Request::get('start');
        $endAt = Request::get('end');

        $eventResults = $this->fireEvent('calendar.generateEvents', [$startAt, $endAt]);

        $generatedEvents = [];
        if (count($eventResults)) {
            $generatedEvents = $eventResults[0];
        }

        return [
            'generatedEvents' => $generatedEvents,
        ];
    }

    public function onUpdateEvent()
    {
        $eventId = Request::get('eventId');
        $startAt = Request::get('start');
        $endAt = Request::get('end');

        $this->fireEvent('calendar.updateEvent', [$eventId, $startAt, $endAt]);
    }

    public function renderPopoverPartial()
    {
        if (!strlen($this->popoverPartial)) {
            throw new Exception(sprintf(lang('igniter::admin.calendar.missing_partial'), get_class($this->controller)));
        }

        return $this->makePartial($this->popoverPartial);
    }
}
