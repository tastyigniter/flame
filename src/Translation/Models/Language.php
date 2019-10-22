<?php

namespace Igniter\Flame\Translation\Models;

use Igniter\Flame\Database\Model;

class Language extends Model
{
    /**
     *  Table name in the database.
     * @var string
     */
    protected $table = 'languages';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'language_id';

    protected function getTranslationLoader()
    {
        return app('translation.loader');
    }
}