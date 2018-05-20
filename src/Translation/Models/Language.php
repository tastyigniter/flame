<?php

namespace Igniter\Flame\Translation\Models;

use File;
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

    /**
     *  Each language may have several translations.
     */
    public function translations()
    {
        return $this->hasMany(Translation::class, 'locale', 'code');
    }

    public function listAllFiles()
    {
        $result = [];
        if (!$this->idiom)
            return $result;

        $namespaces = $this->getLoaderManager()->namespaces();
        foreach ($namespaces as $namespace => $folder) {
            foreach (File::glob($folder.'/'.$this->code.'/*.php') as $filePath) {
                $path = pathinfo($filePath, PATHINFO_FILENAME);
                $key = in_array(ucfirst($namespace), config('system.modules', [])) ? $namespace : 'Other';

                $result[$key][] = (object)[
                    'path'      => $path,
                    'namespace' => $namespace,
                ];
            }
        }

        return $result;
    }

    public function getTranslations($group, $namespace = null)
    {
        $lines = $this->getLoaderManager()->load($this->code, $group, $namespace);

        ksort($lines);

        return array_dot($lines);
    }

    public function updateTranslations($group, $namespace = null, array $lines = [])
    {
        return collect($lines)->map(function ($text, $key) use ($group, $namespace) {
            $oldText = Lang::get("{$namespace}::{$group}.{$key}");

            if (strcmp($text, $oldText) === 0)
                return FALSE;

            $this->translations()->updateOrCreate([
                'group'     => $group,
                'namespace' => $namespace,
                'item'      => $key,
            ], [
                'text' => $text,
            ]);

            return $text;
        })->filter()->toArray();
    }

    protected function getLoaderManager()
    {
        return app('translation.loader');
    }
}