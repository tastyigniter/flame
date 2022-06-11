<?php

namespace Igniter\Admin\FormWidgets;

use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Models\Location;
use Igniter\Admin\Models\WorkingHour;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Location\OrderTypes;
use Illuminate\Support\Facades\DB;

class ScheduleEditor extends BaseFormWidget
{
    use ValidatesForm;

    /**
     * @var \Igniter\Admin\Models\Location Form model object.
     */
    public $model;

    public $form;

    public $popupSize = 'modal-lg';

    public $formTitle = 'igniter::admin.locations.text_title_schedule';

    protected $availableSchedules = [
        Location::OPENING,
        Location::DELIVERY,
        Location::COLLECTION,
    ];

    protected $schedulesCache;

    public function initialize()
    {
        $this->fillFromConfig([
            'form',
        ]);
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('scheduleeditor/scheduleeditor');
    }

    public function prepareVars()
    {
        $this->vars['field'] = $this->formField;
        $this->vars['schedules'] = $this->listSchedules();
    }

    public function loadAssets()
    {
        $this->addCss('formwidgets/clockpicker.css', 'clockpicker-css');
        $this->addJs('formwidgets/clockpicker.js', 'clockpicker-js');

        $this->addJs('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
        $this->addJs('vendor/timesheet/timesheet.js', 'timesheet-js');
        $this->addJs('scheduleeditor.js', 'scheduleeditor-js');
        $this->addCss('vendor/timesheet/timesheet.css', 'timesheet-css');
        $this->addCss('scheduleeditor.css', 'scheduleeditor-css');
    }

    public function onLoadRecord()
    {
        $scheduleCode = post('recordId');
        $scheduleItem = $this->getSchedule($scheduleCode);

        $formTitle = sprintf(lang($this->formTitle), lang($scheduleItem->name));

        return $this->makePartial('recordeditor/form', [
            'formRecordId' => $scheduleCode,
            'formTitle' => $formTitle,
            'formWidget' => $this->makeScheduleFormWidget($scheduleItem),
        ]);
    }

    public function onSaveRecord()
    {
        $scheduleCode = post('recordId');
        $scheduleItem = $this->getSchedule($scheduleCode);

        $form = $this->makeScheduleFormWidget($scheduleItem);
        $saveData = $form->getSaveData();

        $this->validateFormWidget($form, $saveData);

        DB::transaction(function () use ($scheduleCode, $saveData) {
            $this->model->updateSchedule($scheduleCode, $saveData);

            // Check overlaps
            $this->model->newWorkingSchedule($scheduleCode);
        });

        $formName = sprintf('%s %s', $scheduleCode, lang('igniter::admin.locations.text_schedule'));
        flash()->success(sprintf(lang('igniter::admin.alert_success'), ucfirst($formName).' '.'updated'))->now();

        $this->model->reloadRelations();
        $this->schedulesCache = null;

        $this->prepareVars();

        return [
            '#notification' => $this->makePartial('flash'),
            '#'.$this->getId('schedules') => $this->makePartial('scheduleeditor/schedules'),
        ];
    }

    protected function getSchedule($scheduleCode)
    {
        if (!$schedule = array_get($this->listSchedules(), $scheduleCode))
            throw new ApplicationException(lang('igniter::admin.locations.alert_schedule_not_loaded'));

        return $schedule;
    }

    protected function listSchedules()
    {
        if ($this->schedulesCache)
            return $this->schedulesCache;

        $schedules = collect(resolve(OrderTypes::class)->listOrderTypes())
            ->prepend(['name' => 'igniter::admin.text_opening'], Location::OPENING)
            ->mapWithKeys(function ($definition, $code) {
                $scheduleItem = $this->model->createScheduleItem($code);
                $scheduleItem->name = array_get($definition, 'name');

                return [$code => $scheduleItem];
            })
            ->all();

        return $this->schedulesCache = $schedules;
    }

    protected function makeScheduleFormWidget($scheduleItem)
    {
        $widgetConfig = is_string($this->form) ? $this->loadConfig($this->form, ['form'], 'form') : $this->form;
        $widgetConfig['model'] = WorkingHour::make();
        $widgetConfig['data'] = $scheduleItem;
        $widgetConfig['alias'] = $this->alias.'FormScheduleEditor';
        $widgetConfig['arrayName'] = $this->formField->arrayName.'[scheduleData]';
        $widgetConfig['context'] = 'edit';
        $widget = $this->makeWidget(Form::class, $widgetConfig);

        $widget->bindToController();
        $widget->previewMode = $this->previewMode;

        return $widget;
    }
}
