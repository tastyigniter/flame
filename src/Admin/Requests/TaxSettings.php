<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class TaxSettings extends FormRequest
{
    public function attributes()
    {
        return [
            'tax_mode' => lang('igniter::system.settings.label_tax_mode'),
            'tax_title' => lang('igniter::system.settings.label_tax_title'),
            'tax_percentage' => lang('igniter::system.settings.label_tax_percentage'),
            'tax_menu_price' => lang('igniter::system.settings.label_tax_menu_price'),
            'tax_delivery_charge' => lang('igniter::system.settings.label_tax_delivery_charge'),
        ];
    }

    public function rules()
    {
        return [
            'tax_mode' => ['required', 'integer'],
            'tax_title' => ['string', 'max:32'],
            'tax_percentage' => ['required_if:tax_mode,1', 'numeric'],
            'tax_menu_price' => ['numeric'],
            'tax_delivery_charge' => ['numeric'],
        ];
    }
}
