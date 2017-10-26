<?php

namespace Igniter\Flame\Pagination;

class Paginator extends \Illuminate\Pagination\Paginator
{
    public function render($view = null, $data = [])
    {
        return $this->ci()->load->partial($view ?: static::$defaultView, array_merge($data, [
            'paginator' => $this,
            'elements'  => $this->elements(),
        ]));
    }
}