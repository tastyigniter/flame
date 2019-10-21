<?php

namespace Igniter\Flame\Translation\Models;

use Igniter\Flame\Database\Model;
use Lang;

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

    /**
     *  List of variables that cannot be mass assigned
     * @var array
     */
    protected $fillable = ['code', 'name'];

    public function getTranslations($group, $namespace = null)
    {
        $lines = $this->getTranslationLoader()->load($this->code, $group, $namespace);

        ksort($lines);

        return array_dot($lines);
    }

    public function updateTranslations($group, $namespace = null, array $lines = [])
    {
        return collect($lines)->map(function ($text, $key) use ($group, $namespace) {
            $this->updateTranslation($group, $namespace, $key, $text);

            return $text;
        })->filter()->toArray();
    }

    public function updateTranslation($group, $namespace, $key, $text)
    {
        $oldText = Lang::get("{$namespace}::{$group}.{$key}");

        if (strcmp($text, $oldText) === 0)
            return FALSE;

        $translation = $this->translations()->firstOrNew([
            'group' => $group,
            'namespace' => $namespace,
            'item' => $key,
        ]);

        $translation->updateAndLock($text);
    }

    protected function getTranslationLoader()
    {
        return app('translation.loader');
    }
}