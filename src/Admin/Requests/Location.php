<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class Location extends FormRequest
{
    public function attributes()
    {
        return [
            'location_name' => lang('igniter::admin.label_name'),
            'location_email' => lang('igniter::admin.label_email'),
            'location_telephone' => lang('igniter::admin.locations.label_telephone'),
            'location_address_1' => lang('igniter::admin.locations.label_address_1'),
            'location_address_2' => lang('igniter::admin.locations.label_address_2'),
            'location_city' => lang('igniter::admin.locations.label_city'),
            'location_state' => lang('igniter::admin.locations.label_state'),
            'location_postcode' => lang('igniter::admin.locations.label_postcode'),
            'location_country_id' => lang('igniter::admin.locations.label_country'),
            'options.auto_lat_lng' => lang('igniter::admin.locations.label_auto_lat_lng'),
            'location_lat' => lang('igniter::admin.locations.label_latitude'),
            'location_lng' => lang('igniter::admin.locations.label_longitude'),
            'description' => lang('igniter::admin.label_description'),
            'location_status' => lang('igniter::admin.label_status'),
            'permalink_slug' => lang('igniter::admin.locations.label_permalink_slug'),
            'gallery.title' => lang('igniter::admin.locations.label_gallery_title'),
            'gallery.description' => lang('igniter::admin.label_description'),
        ];
    }

    public function rules()
    {
        return [
            'location_name' => ['required', 'string', 'between:2,32', 'unique:locations'],
            'location_email' => ['required', 'email:filter', 'max:96'],
            'location_telephone' => ['sometimes', 'string'],
            'location_address_1' => ['required', 'string', 'between:2,128'],
            'location_address_2' => ['string', 'max:128'],
            'location_city' => ['string', 'max:128'],
            'location_state' => ['string', 'max:128'],
            'location_postcode' => ['string', 'max:15'],
            'location_country_id' => ['required', 'integer'],
            'options.auto_lat_lng' => ['required', 'boolean'],
            'location_lat' => ['sometimes', 'numeric'],
            'location_lng' => ['sometimes', 'numeric'],
            'description' => ['max:3028'],
            'location_status' => ['boolean'],
            'permalink_slug' => ['alpha_dash', 'max:255'],
            'gallery.title' => ['string', 'max:128'],
            'gallery.description' => ['string', 'max:255'],
        ];
    }
}
