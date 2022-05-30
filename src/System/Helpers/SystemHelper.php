<?php

namespace Igniter\System\Helpers;

class SystemHelper
{
    public static function replaceInEnv(string $search, string $replace)
    {
        $file = base_path().'/.env';

        file_put_contents(
            $file,
            preg_replace('/^'.$search.'(.*)$/m', $replace, file_get_contents($file))
        );

        putenv($replace);
    }
}
