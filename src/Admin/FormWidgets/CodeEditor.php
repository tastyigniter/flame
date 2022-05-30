<?php

namespace Igniter\Admin\FormWidgets;

use Igniter\Admin\Classes\BaseFormWidget;

/**
 * Code Editor
 * Renders a code editor field.
 */
class CodeEditor extends BaseFormWidget
{
    //
    // Configurable properties
    //

    public $mode = 'css';

    public $theme = 'material';

    /**
     * @var bool Determines whether content has HEAD and HTML tags.
     */
    public $fullPage = false;

    public $lineSeparator;

    public $readOnly = false;

    //
    // Object properties
    //

    protected $defaultAlias = 'codeeditor';

    public function initialize()
    {
        $this->fillFromConfig([
            'fullPage',
            'lineSeparator',
            'mode',
            'theme',
            'readOnly',
        ]);
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('codeeditor/codeeditor');
    }

    public function loadAssets()
    {
        $this->addJs('js/vendor.editor.js', 'vendor-editor-js');
        $this->addCss('codeeditor.css', 'codeeditor-css');
        $this->addJs('codeeditor.js', 'codeeditor-js');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['field'] = $this->formField;
        $this->vars['fullPage'] = $this->fullPage;
        $this->vars['stretch'] = $this->formField->stretch;
        $this->vars['size'] = $this->formField->size;
        $this->vars['lineSeparator'] = $this->lineSeparator;
        $this->vars['readOnly'] = $this->readOnly;
        $this->vars['mode'] = $this->mode;
        $this->vars['theme'] = $this->theme;
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
    }
}
