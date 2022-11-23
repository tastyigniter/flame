<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\System\Models\Language;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use ZipArchive;

class LanguageManager
{
    /**
     * @var \Igniter\Flame\Translation\FileLoader
     */
    protected $loader;

    /**
     * @var \Igniter\Flame\Filesystem\Filesystem
     */
    protected $files;

    protected $langPath;

    /**
     * @var array of languages and their directory paths.
     */
    protected $paths = [];

    public function initialize()
    {
        $this->loader = App::make('translation.loader');
        $this->files = App::make('files');
        $this->langPath = App::langPath();

        $this->hubManager = resolve(HubManager::class);
    }

    public function namespaces()
    {
        $namespaces = $this->loader->namespaces();
        asort($namespaces);

        return $namespaces;
    }

    public function listLanguages()
    {
        return Language::isEnabled()->get();
    }

    /**
     * Create a Directory Map of all themes
     * @return array A list of all themes in the system.
     */
    public function paths()
    {
        if ($this->paths)
            return $this->paths;

        $paths = [];

        if (!File::exists($directory = base_path('language')))
            return $paths;

//        $directories = array_merge([Igniter::themesPath()], self::$directories);
//        foreach ($directories as $directory) {
        foreach (File::directories($directory) as $path) {
            $langDir = basename($path);
            $paths[$langDir] = $path;
        }

//        }

        return $this->paths = $paths;
    }

    //
    // Translations
    //

    public function listLocaleFiles($locale)
    {
        $result = [];
        $namespaces = $this->loader->namespaces();
        asort($namespaces);
        foreach ($namespaces as $namespace => $folder) {
            foreach (File::glob($folder.'/'.$locale.'/*.php') as $filePath) {
                $result[] = [
                    'namespace' => $namespace,
                    'group' => pathinfo($filePath, PATHINFO_FILENAME),
                    'system' => in_array(ucfirst($namespace), config('igniter.system.modules', [])),
                ];
            }
        }

        return $result;
    }

    public function listTranslations($sourceLines, $translationLines, $options = [])
    {
        $file = array_get($options, 'file');
        $stringFilter = array_get($options, 'stringFilter');

        $result = [];
        foreach ($sourceLines as $key => $sourceLine) {
            $translationLine = array_get($translationLines, $key, $sourceLine);

            if ($stringFilter === 'changed' && !array_has($translationLines, $key)) continue;

            if ($stringFilter === 'unchanged' && array_has($translationLines, $key)) continue;

            if ((!is_null($sourceLine) && !is_string($sourceLine))) continue;

            if ((!is_null($translationLine) && !is_string($translationLine))) continue;

            $namespacedKey = sprintf('%s::%s.%s', $file['namespace'], $file['group'], $key);

            $result[$namespacedKey] = [
                'source' => $sourceLine,
                'translation' => $translationLine,
            ];
        }

        return $result;
    }

    public function searchTranslations($translations, $term = null)
    {
        if (!strlen($term))
            return $translations;

        $result = [];
        $term = strtolower($term);
        foreach ($translations as $key => $value) {
            if (strlen($term)) {
                if (stripos(strtolower(array_get($value, 'source')), $term) !== false
                    || stripos(strtolower(array_get($value, 'translation')), $term) !== false
                    || stripos(strtolower($key), $term) !== false) {
                    $result[$key] = $value;
                }
            }
            else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function paginateTranslations($translations, $perPage = 50)
    {
        $page = Paginator::resolveCurrentPage();

        $items = collect($translations);
        $total = $items->count();

        $items = $total ? $items->forPage($page, $perPage) : collect();

        $options = [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ];

        return App::makeWith(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'page', 'options'
        ));
    }

    public function canUpdate(Language $language)
    {
        return !in_array($language->code, ['en', 'en_US', 'en_GB']) && $language->can_update;
    }

    //
    //
    //

    public function searchLanguages($term)
    {
        $items = $this->getHubManager()->listLanguages([
            'search' => $term,
        ]);

        if (isset($items['data'])) foreach ($items['data'] as &$item) {
            $item['require'] = [];
        }

        return $items;
    }

    public function applyLanguagePack($locale, $build = null)
    {
        $response = $this->getHubManager()->applyLanguagePack($locale, $build);

        return array_get($response, 'data', []);
    }

    public function downloadPack($meta)
    {
        $packCode = array_get($meta, 'code');
        $packHash = array_get($meta, 'hash');

        $filePath = $this->getFilePath($packCode);
        if (!is_dir($fileDir = dirname($filePath)))
            mkdir($fileDir, 0777, true);

        return $this->getHubManager()->downloadLanguagePack($filePath, $packHash, [
            'locale' => $packCode,
            'build' => array_get($meta, 'version'),
        ]);
    }

    public function extractPack($meta)
    {
        $packCode = array_get($meta, 'code');

        $filePath = $this->getFilePath($packCode);
        $extractTo = app()->langPath().'/'.$packCode;
        if (!file_exists($extractTo))
            mkdir($extractTo, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $zip->extractTo($extractTo);
            $zip->close();
            @unlink($filePath);

            return true;
        }

        throw new ApplicationException('Failed to extract '.$packCode.' archive file');
    }

    public function installPack($item)
    {
        $model = Language::firstOrCreate(['code' => $item['code']]);
        $model->name = $item['name'];
        $model->version = $item['version'];
        $model->save();

        return true;
    }

    public function getFilePath($packCode)
    {
        $fileName = md5($packCode).'.zip';

        return storage_path("temp/{$fileName}");
    }

    /**
     * @return \Igniter\System\Classes\HubManager
     */
    protected function getHubManager()
    {
        return $this->hubManager;
    }
}
