<?php

namespace Igniter\Admin\FormWidgets;

use Exception;
use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Classes\FormField;
use Igniter\Admin\Models\MenuOption;
use Igniter\Admin\Traits\FormModelWidget;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Exception\ApplicationException;
use Illuminate\Support\Facades\DB;

/**
 * Menu Option Editor Widget
 */
class MenuOptionEditor extends BaseFormWidget
{
    use FormModelWidget;
    use ValidatesForm;

    const INDEX_SEARCH = '___index__';

    const SORT_PREFIX = '___dragged_';

    //
    // Object properties
    //

    protected $defaultAlias = 'menuoptioneditor';

    protected $modelClass = MenuOption::class;

    //
    // Configurable properties
    //

    public $formName = 'Record';

    /**
     * @var array Form field configuration
     */
    public $form;

    public $pickerPlaceholder = 'lang:igniter::admin.menu_options.help_menu_option';

    public $newRecordTitle = 'New %s';

    public $editRecordTitle = 'Edit %s';

    public $emptyMessage = 'igniter::admin.list.text_empty';

    public $confirmMessage = 'igniter::admin.alert_warning_confirm';

    public $popupSize = 'modal-lg';

    public function initialize()
    {
        $this->fillFromConfig([
            'form',
            'formName',
            'pickerPlaceholder',
            'emptyMessage',
            'confirmMessage',
            'popupSize',
        ]);

        if ($this->formField->disabled || $this->formField->readOnly) {
            $this->previewMode = true;
        }
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('menuoptioneditor/menuoptioneditor');
    }

    public function loadAssets()
    {
        $this->addJs('formwidgets/repeater.js', 'repeater-js');

        $this->addJs('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
        $this->addJs('formwidgets/recordeditor.js', 'recordeditor-js');

        $this->addJs('menuoptioneditor.js', 'menuoptioneditor-js');
    }

    public function getSaveValue($value)
    {
        return FormField::NO_SAVE_DATA;
    }

    /**
     * Prepares the view data
     */
    public function prepareVars()
    {
        $this->vars['formField'] = $this->formField;
        $this->vars['fieldItems'] = $this->getLoadValue();

        $this->vars['pickerPlaceholder'] = $this->pickerPlaceholder;

        $this->vars['emptyMessage'] = $this->emptyMessage;
        $this->vars['confirmMessage'] = $this->confirmMessage;
    }

    public function reload()
    {
        $this->formField->value = null;
        $this->model->reloadRelations();

        $this->prepareVars();

        return [
            '#notification' => $this->makePartial('flash'),
            '#'.$this->getId('items') => $this->makePartial('menuoptioneditor/items'),
        ];
    }

    public function onAssignRecord()
    {
        $menuOptionId = post('optionId');
        if (!$menuOption = MenuOption::find($menuOptionId))
            throw new ApplicationException(lang('igniter::admin.menu_options.alert_menu_option_not_attached'));

        if ($this->model->menu_option_values()->where('option_id', $menuOptionId)->exists())
            throw new ApplicationException(lang('igniter::admin.menu_options.alert_menu_option_already_attached'));

        $menuOption->option_values()->get()->each(function ($model) {
            $this->model->menu_option_values()->create([
                'option_id' => $model->option_id,
                'option_value_id' => $model->option_value_id,
                'priority' => $model->priority,
            ]);
        });

        flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Menu item option assigned'))->now();

        return $this->reload();
    }

    public function onLoadRecord()
    {
        $formTitle = lang($this->editRecordTitle);

        if (!strlen($recordId = post('recordId')))
            throw new ApplicationException(lang('igniter::admin.form.missing_id'));

        $model = $this->getLoadValue()->firstWhere('option_id', $recordId);

        if (!$model)
            throw new Exception(sprintf(lang('igniter::admin.form.not_found'), $recordId));

        return $this->makePartial('recordeditor/form', [
            'formRecordId' => $recordId,
            'formTitle' => sprintf($formTitle, lang($this->formName)),
            'formWidget' => $this->makeItemFormWidget($model, 'edit'),
        ]);
    }

    public function onSaveRecord()
    {
        if (!strlen($recordId = post('recordId')))
            throw new ApplicationException(lang('igniter::admin.form.missing_id'));

        $model = $this->getLoadValue()->firstWhere('option_id', $recordId);

        $form = $this->makeItemFormWidget($model, 'edit');

        $saveData = $this->prepareSaveData($model, $form->getSaveData());

        DB::transaction(function () use ($saveData) {
            $this->model->addMenuOptionValues($saveData);
        });

        flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Item updated'))->now();

        return $this->reload();
    }

    public function onDeleteRecord()
    {
        if (!strlen($recordId = post('recordId')))
            throw new ApplicationException(lang('igniter::admin.form.missing_id'));

        $this->model->menu_option_values()
            ->where('option_id', $recordId)
            ->delete();

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang($this->formName).' deleted'))->now();

        $this->prepareVars();

        return [
            '#notification' => $this->makePartial('flash'),
        ];
    }

    protected function getPickerOptions()
    {
        return $this->modelClass::getRecordEditorOptions();
    }

    protected function makeItemFormWidget($model, $context)
    {
        $widgetConfig = is_string($this->form) ? $this->loadConfig($this->form, ['form'], 'form') : $this->form;
        $widgetConfig['model'] = $model;
        $widgetConfig['alias'] = $this->alias.'FormMenuOptionEditor';
        $widgetConfig['arrayName'] = $this->formField->arrayName.'[menuOptionEditorData]';
        $widgetConfig['context'] = $context;
        $widget = $this->makeWidget(Form::class, $widgetConfig);

        $widget->bindToController();
        $widget->previewMode = $this->previewMode;

        return $widget;
    }

    protected function prepareSaveData($model, $saveData)
    {
        $optionValues = collect(array_get($saveData, 'menu_option_values'))
            ->map(function ($optionValue) {
                $optionValue['new_price'] = $optionValue['price'];
                unset($optionValue['price']);

                return $optionValue;
            })
            ->all();

        return [$model->getKey() => $optionValues];
    }
}
