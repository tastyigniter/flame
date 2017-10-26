<?php

namespace Igniter\Flame\Pagination;

use Illuminate\Support\HtmlString;

class LengthAwarePaginator extends \Illuminate\Pagination\LengthAwarePaginator
{
//    public function render($view = null, $data = [])
//    {
//        dd($view ?: static::$defaultView, static::viewFactory()->make($view ?: static::$defaultView));
//        return new HtmlString(static::viewFactory()->make($view ?: static::$defaultView, array_merge($data, [
//            'paginator' => $this,
//            'elements' => $this->elements(),
//        ]))->render());
////        return $this->ci()->load->partial($view ?: static::$defaultView, array_merge($data, [
////            'paginator' => $this,
////            'elements' => $this->elements(),
////        ]));
//    }
}