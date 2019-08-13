<?php

namespace Igniter\Flame\Support;

use App;

/**
 * Pagic helper class
 */
class PagicHelper
{
    /**
     * Parses supplied Blade contents, with supplied variables.
     * @param string $contents Blade contents to parse.
     * @param array $vars Context variables.
     * @return string
     */
    public static function parse($contents, $vars = [])
    {
        $pagic = App::make('pagic.environment');
        $template = $pagic->createTemplate($contents);

        return $template->render($vars);
    }
}