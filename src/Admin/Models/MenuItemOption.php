<?php

namespace Igniter\Admin\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\Flame\Database\Traits\Validation;

class MenuItemOption extends Model
{
    use Purgeable;
    use Validation;

    /**
     * @var string The database table name
     */
    protected $table = 'menu_item_options';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'menu_option_id';

    protected $fillable = ['option_id', 'menu_id', 'required', 'priority', 'min_selected', 'max_selected'];

    protected $casts = [
        'menu_option_id' => 'integer',
        'option_id' => 'integer',
        'menu_id' => 'integer',
        'required' => 'boolean',
        'priority' => 'integer',
        'min_selected' => 'integer',
        'max_selected' => 'integer',
    ];

    public $relation = [
        'hasMany' => [
            'option_values' => [\Igniter\Admin\Models\MenuItemOptionValue::class, 'foreignKey' => 'option_id', 'otherKey' => 'option_id'],
            'menu_option_values' => [
                \Igniter\Admin\Models\MenuItemOptionValue::class,
                'foreignKey' => 'menu_option_id',
                'delete' => true,
            ],
        ],
        'belongsTo' => [
            'menu' => [\Igniter\Admin\Models\Menu::class],
            'option' => [\Igniter\Admin\Models\MenuOption::class],
        ],
    ];

    public $appends = ['option_name', 'display_type'];

    public $rules = [
        ['menu_id', 'igniter::admin.menus.label_option', 'required|integer'],
        ['option_id', 'igniter::admin.menus.label_option_id', 'required|integer'],
        ['priority', 'igniter::admin.menu_options.label_option', 'integer'],
        ['required', 'igniter::admin.menu_options.label_option_required', 'boolean'],
        ['min_selected', 'igniter::admin.menu_options.label_min_selected', 'integer|lte:max_selected'],
        ['max_selected', 'igniter::admin.menu_options.label_max_selected', 'integer|gte:min_selected'],
    ];

    protected $purgeable = ['menu_option_values'];

    public $with = ['option'];

    public $timestamps = true;

    public function getOptionNameAttribute($value = null)
    {
        return $value ?: optional($this->option)->option_name;
    }

    public function getDisplayTypeAttribute()
    {
        return optional($this->option)->display_type;
    }

    //
    // Events
    //
    protected function afterSave()
    {
        $this->restorePurgedValues();

        if (array_key_exists('menu_option_values', $this->attributes))
            $this->addMenuOptionValues($this->attributes['menu_option_values']);
    }

    //
    // Helpers
    //
    public function isRequired()
    {
        return $this->required;
    }

    public function isSelectDisplayType()
    {
        return $this->display_type === 'select';
    }

    /**
     * Create new or update existing menu option values
     *
     * @param array $optionValues if empty all existing records will be deleted
     *
     * @return bool
     */
    public function addMenuOptionValues(array $optionValues = [])
    {
        $menuOptionId = $this->getKey();
        if (!is_numeric($menuOptionId))
            return false;

        $idsToKeep = [];
        foreach ($optionValues as $value) {
            $menuOptionValue = $this->menu_option_values()->firstOrNew([
                'menu_option_value_id' => array_get($value, 'menu_option_value_id'),
                'menu_option_id' => $menuOptionId,
            ])->fill(array_except($value, ['menu_option_value_id', 'menu_option_id']));
            $menuOptionValue->saveOrFail();
            $idsToKeep[] = $menuOptionValue->getKey();
        }

        $this->menu_option_values()
            ->where('menu_option_id', $menuOptionId)
            ->whereNotIn('menu_option_value_id', $idsToKeep)
            ->delete();

        return count($idsToKeep);
    }
}
