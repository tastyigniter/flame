<?php

namespace Igniter\Flame\Mail;

use Igniter\Flame\Support\Facades\File;

class Markdown extends \Illuminate\Mail\Markdown
{
    public static function parseFile($path)
    {
        return self::parse(File::get($path));
    }
}
