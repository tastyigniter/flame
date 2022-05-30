<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class Order extends FormRequest
{
    protected function useDataFrom()
    {
        return static::DATA_TYPE_POST;
    }

    public function rules()
    {
        return [];
    }
}
