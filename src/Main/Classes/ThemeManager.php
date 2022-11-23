<?php

namespace Igniter\Main\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Igniter;
use Igniter\Main\Events\Theme\GetActiveTheme;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\Libraries\Assets;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use ZipArchive;

/**
 * Theme Manager Class
 */
class ThemeManager
{
    protected $themeModel = \Igniter\Main\Models\Theme::class;

    /**
     * @var array of disabled themes.
     */
    public $installedThemes = [];

    /**
     * @var array used for storing theme information objects.
     */
    public $themes = [];

    public $activeTheme;

    /**
     * @var array of themes and their directory paths.
     */
    protected $paths = [];

    protected $config = [
        'allowedImageExt' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'],
        'allowedFileExt' => ['html', 'txt', 'xml', 'js', 'css', 'php', 'json'],
    ];

    protected $booted = false;

    protected static $directories = [];

    public function initialize()
    {
        // This prevents reading settings from the database before its been created
        $this->loadInstalled();
        $this->loadThemes();
    }

    public static function addDirectory($directory)
    {
        self::$directories[] = $directory;
    }

    public function addAssetsFromActiveThemeManifest(Assets $manager)
    {
        if (!$theme = $this->getActiveTheme())
            return;

        if (File::exists($theme->getSourcePath().'/_meta/assets.json')) {
            $manager->addFromManifest($theme->getSourcePath().'/_meta/assets.json');
        }
        elseif ($theme->hasParent()) {
            $manager->addFromManifest($theme->getParent()->getSourcePath().'/_meta/assets.json');
        }
    }

    public function applyAssetVariablesOnCombinerFilters(array $filters, Theme $theme = null)
    {
        $theme = !is_null($theme) ? $theme : $this->getActiveTheme();

        if (!$theme || !$theme->hasCustomData())
            return;

        $assetVars = $theme->getAssetVariables();
        foreach ($filters as $filter) {
            if (method_exists($filter, 'setVariables')) {
                $filter->setVariables($assetVars);
            }
        }
    }

    //
    // Registration Methods
    //

    /**
     * Returns a list of all themes in the system.
     * @return array A list of all themes in the system.
     */
    public function listThemes()
    {
        return $this->themes;
    }

    /**
     * Loads all installed theme from application config.
     */
    public function loadInstalled()
    {
        $this->installedThemes = resolve(PackageManifest::class)->installThemes();
    }

    /**
     * Finds all available themes and loads them in to the $themes array.
     * @return array
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function loadThemes()
    {
        foreach (resolve(PackageManifest::class)->themes() as $code => $config) {
            $this->loadThemeFromConfig($code, $config);
        }

        foreach ($this->folders() as $path) {
            $this->loadTheme($path);
        }

        return $this->themes;
    }

    public function loadThemeFromConfig($code, $config)
    {
        if (isset($this->themes[$code]))
            return $this->themes[$code];

        if (!$this->checkName($code)) return false;

        $config = $this->validateMetaFile($config, $code);

        $path = array_get($config, 'directory');
        $themeObject = new Theme($path, $config);

        $themeObject->active = $this->isActive($code);

        $this->themes[$code] = $themeObject;
        $this->paths[$code] = $themeObject->getPath();

        return $themeObject;
    }

    /**
     * Loads a single theme in to the manager.
     *
     * @param string $path
     *
     * @return bool|object
     */
    public function loadTheme($path)
    {
        if (!$config = $this->getMetaFromFile($path, false))
            return false;

        $config['directory'] = $path;

        return $this->loadThemeFromConfig(basename($path), $config);
    }

    public function bootThemes()
    {
        if ($this->booted)
            return;

        if (!$this->themes)
            $this->loadThemes();

        foreach ($this->themes as $theme) {
            $theme->boot();
        }

        $this->booted = true;
    }

    //
    // Management Methods
    //

    public function getActiveTheme()
    {
        return ($activeTheme = $this->findTheme($this->getActiveThemeCode()))
            ? $activeTheme : null;
    }

    public function getActiveThemeCode()
    {
        $activeTheme = trim(params('default_themes.main', config('igniter.system.defaultTheme')), '/');

        event($event = new GetActiveTheme($activeTheme), [], true);

        return $event->getCode();
    }

    /**
     * Returns a theme object based on its name.
     *
     * @param $themeCode
     *
     * @return \Igniter\Main\Classes\Theme|null
     */
    public function findTheme($themeCode)
    {
        if (!$this->hasTheme($themeCode)) {
            return null;
        }

        return $this->themes[$themeCode];
    }

    /**
     * Checks to see if an extension has been registered.
     *
     * @param $themeCode
     *
     * @return bool
     */
    public function hasTheme($themeCode)
    {
        return isset($this->themes[$themeCode]);
    }

    /**
     * Returns the theme domain by looking in its path.
     *
     * @param $themeCode
     *
     * @return \Igniter\Main\Classes\Theme|null
     */
    public function findParent($themeCode)
    {
        $theme = $this->findTheme($themeCode);

        return $theme ? $this->findTheme($theme->getParentName()) : null;
    }

    /**
     * Returns the parent theme code.
     *
     * @param $themeCode
     *
     * @return string
     */
    public function findParentCode($themeCode)
    {
        $theme = $this->findTheme($themeCode);

        return $theme ? $theme->getParentName() : null;
    }

    public function paths()
    {
        return $this->paths;
    }

    /**
     * Create a Directory Map of all themes
     * @return array A list of all themes in the system.
     */
    public function folders()
    {
        $paths = [];

        $directories = self::$directories;
        if (File::isDirectory($themesPath = Igniter::themesPath()))
            array_unshift($directories, $themesPath);

        foreach ($directories as $directory) {
            foreach (File::glob($directory.'/*/theme.json') as $path) {
                $paths[] = dirname($path);
            }
        }

        return $paths;
    }

    /**
     * Determines if a theme is activated by looking at the default themes config.
     *
     * @param $themeCode
     *
     * @return bool
     */
    public function isActive($themeCode)
    {
        if (!$this->checkName($themeCode)) {
            return false;
        }

        return rtrim($themeCode, '/') == $this->getActiveThemeCode();
    }

    /**
     * Determines if a theme is disabled by looking at the installed themes config.
     *
     * @param $name
     *
     * @return bool
     */
    public function isDisabled($name)
    {
        traceLog('Deprecated. Use $instance::isActive($themeCode) instead');

        return !$this->checkName($name) || !array_get($this->installedThemes, $name, false);
    }

    /**
     * Checks to see if a theme has been registered.
     *
     * @param $themeCode
     *
     * @return bool
     */
    public function checkName($themeCode)
    {
        if ($themeCode == 'errors')
            return null;

        return (str_starts_with($themeCode, '_') || preg_match('/\s/', $themeCode)) ? null : $themeCode;
    }

    /**
     * Search a theme folder for files.
     *
     * @param string $themeCode The theme to search
     * @param string $subFolder If not null, will return only files within sub-folder (ie 'partials').
     *
     * @return array $theme_files
     */
    public function listFiles($themeCode, $subFolder = null)
    {
        traceLog('Deprecated. Use Template::listInTheme($theme) instead');
        $result = [];
        $themePath = $this->findPath($themeCode);
        $files = File::allFiles($themePath);
        foreach ($files as $file) {
            [$folder,] = explode('/', $file->getRelativePath());
            $path = $file->getRelativePathname();
            $result[$folder ?: '/'][] = $path;
        }

        if (is_string($subFolder))
            $subFolder = [$subFolder];

        return $subFolder ? array_only($result, $subFolder) : $result;
    }

    public function isLocked($themeCode)
    {
        return (bool)optional($this->findTheme($themeCode))->locked;
    }

    public function checkParent($themeCode)
    {
        foreach ($this->themes as $theme) {
            if ($theme->hasParent() && $theme->getParentName() == $themeCode)
                return true;
        }

        return false;
    }

    public function isLockedPath($path)
    {
        if (starts_with($path, Igniter::themesPath().'/'))
            $path = substr($path, strlen(Igniter::themesPath().'/'));

        $themeCode = str_before($path, '/');

        return $this->isLocked($themeCode);
    }

    //
    // Theme Helper Methods
    //

    /**
     * Returns a theme path based on its name.
     *
     * @param $themeCode
     *
     * @return string|null
     */
    public function findPath($themeCode)
    {
        return $this->paths()[$themeCode] ?? null;
    }

    /**
     * Find a file.
     * Scans for files located within themes directories. Also scans each theme
     * directories for layouts, partials, and content. Generates fatal error if file
     * not found.
     *
     * @param string $filename The file.
     * @param string $themeCode The theme code.
     * @param string $base The folder within the theme eg. layouts, partials, content
     *
     * @return string|bool
     */
    public function findFile($filename, $themeCode, $base = null)
    {
        $path = $this->findPath($themeCode);

        $themePath = rtrim($path, '/');
        if (is_null($base)) {
            $base = ['/'];
        }
        elseif (!is_array($base)) {
            $base = [$base];
        }

        foreach ($base as $folder) {
            if (File::isFile($path = $themePath.$folder.$filename)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * Load a single theme generic file into an array. The file will be
     * found by looking in the _layouts, _pages, _partials, _content, themes folders.
     *
     * @param string $filePath The name of the file to locate.
     * @param string $themeCode The theme to check.
     *
     * @return \Igniter\Flame\Pagic\Contracts\TemplateSource
     */
    public function readFile($filePath, $themeCode)
    {
        $theme = $this->findTheme($themeCode);

        [$dirName, $fileName] = $this->getFileNameParts($filePath, $theme);

        if (!$template = $theme->onTemplate($dirName)->find($fileName))
            throw new SystemException("Theme template file not found: $filePath");

        return $template;
    }

    public function newFile($filePath, $themeCode)
    {
        $theme = $this->findTheme($themeCode);
        [$dirName, $fileName] = $this->getFileNameParts($filePath, $theme);
        $path = $theme->getPath().'/'.$dirName.'/'.$fileName;

        if (File::isFile($path))
            throw new SystemException("Theme template file already exists: $filePath");

        if (!File::exists($path))
            File::makeDirectory(File::dirname($path), 0777, true, true);

        File::put($path, "\n");
    }

    /**
     * Write an existing theme layout, page, partial or content file.
     *
     * @param string $filePath The name of the file to locate.
     * @param array $attributes
     * @param string $themeCode The theme to check.
     *
     * @return bool
     */
    public function writeFile($filePath, array $attributes, $themeCode)
    {
        $theme = $this->findTheme($themeCode);

        [$dirName, $fileName] = $this->getFileNameParts($filePath, $theme);

        if (!$template = $theme->onTemplate($dirName)->find($fileName))
            throw new SystemException("Theme template file not found: $filePath");

        return $template->update($attributes);
    }

    /**
     * Rename a theme layout, page, partial or content in the file system.
     *
     * @param string $filePath The name of the file to locate.
     * @param string $newFilePath
     * @param string $themeCode The theme to check.
     *
     * @return bool
     */
    public function renameFile($filePath, $newFilePath, $themeCode)
    {
        $theme = $this->findTheme($themeCode);

        [$dirName, $fileName] = $this->getFileNameParts($filePath, $theme);
        [$newDirName, $newFileName] = $this->getFileNameParts($newFilePath, $theme);

        if (!$template = $theme->onTemplate($dirName)->find($fileName))
            throw new SystemException("Theme template file not found: $filePath");

        if ($this->isLockedPath($template->getFilePath()))
            throw new SystemException(lang('igniter::system.themes.alert_theme_path_locked'));

        $oldFilePath = $theme->path.'/'.$dirName.'/'.$fileName;
        $newFilePath = $theme->path.'/'.$newDirName.'/'.$newFileName;

        if ($oldFilePath == $newFilePath)
            throw new SystemException("Theme template file already exists: $filePath");

        return $template->update(['fileName' => $newFileName]);
    }

    /**
     * Delete a theme layout, page, partial or content from the file system.
     *
     * @param string $filePath The name of the file to locate.
     * @param string $themeCode The theme to check.
     *
     * @return bool
     */
    public function deleteFile($filePath, $themeCode)
    {
        $theme = $this->findTheme($themeCode);

        [$dirName, $fileName] = $this->getFileNameParts($filePath, $theme);

        if (!$template = $theme->onTemplate($dirName)->find($fileName))
            throw new SystemException("Theme template file not found: $filePath");

        if ($this->isLockedPath($template->getFilePath()))
            throw new SystemException(lang('igniter::system.themes.alert_theme_path_locked'));

        return $template->delete();
    }

    /**
     * Extract uploaded/downloaded theme zip folder
     *
     * @param string $zipPath The path to the zip folder
     *
     * @return bool
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function extractTheme($zipPath)
    {
        $zip = new ZipArchive;

        $themesFolder = Igniter::themesPath();

        if ($zip->open($zipPath) === true) {
            $themeDir = $zip->getNameIndex(0);

            if ($zip->locateName($themeDir.'theme.json') === false)
                return false;

            if (file_exists($themesFolder.'/'.$themeDir)) {
                throw new SystemException(lang('igniter::system.themes.error_theme_exists'));
            }

            $meta = @json_decode($zip->getFromName($themeDir.'theme.json'));
            if (!$meta || !strlen($meta->code))
                throw new SystemException(lang('igniter::system.themes.error_config_no_found'));

            $themeCode = $meta->code;
            if (!$this->checkName($themeDir) || !$this->checkName($themeCode))
                throw new SystemException('Theme directory name can not have spaces.');

            $extractToPath = $themesFolder.'/'.$themeCode;
            $zip->extractTo($extractToPath);
            $zip->close();

            return $themeCode;
        }

        return false;
    }

    /**
     * Delete existing theme folder from filesystem.
     *
     * @param null $themeCode The theme to delete
     *
     * @return bool
     */
    public function removeTheme($themeCode)
    {
        $themePath = $this->findPath($themeCode);

        if (!is_dir($themePath) || !str_starts_with($themePath, Igniter::themesPath()))
            return false;

        // Delete the specified admin and main language folder.
        File::deleteDirectory($themePath);

        return true;
    }

    public function installTheme($code, $version = null)
    {
        $model = $this->themeModel::firstOrNew(['code' => $code]);

        if (!$themeObj = $this->findTheme($model->code))
            return false;

        $model->name = $themeObj->label ?? title_case($code);
        $model->code = $code;
        $model->version = $version ?? resolve(PackageManifest::class)->getVersion($code) ?? $model->version;
        $model->description = $themeObj->description ?? '';
        $model->save();

        return true;
    }

    /**
     * Update installed extensions config value
     */
    public function updateInstalledThemes($code, $enable = true)
    {
        if (is_null($enable)) {
            array_pull($this->installedThemes, $code);
        }
        else {
            $this->installedThemes[$code] = $enable;
        }

        resolve(PackageManifest::class)->installThemes($this->installedThemes);
    }

    /**
     * @param \Igniter\Main\Models\Theme $model
     * @return \Igniter\Main\Models\Theme
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function createChildTheme($model)
    {
        $parentTheme = $this->findTheme($model->code);
        if ($parentTheme->hasParent())
            throw new SystemException('Can not create a child theme from another child theme');

        $childThemeCode = $this->themeModel::generateUniqueCode($model->code);
        $childThemePath = Igniter::themesPath().'/'.$childThemeCode;

        $themeConfig = [
            'code' => $childThemeCode,
            'name' => $parentTheme->label.' [child]',
            'description' => $parentTheme->description,
        ];

        $this->writeChildThemeMetaFile(
            $childThemePath, $parentTheme, $themeConfig
        );

        $themeConfig['data'] = $model->data ?? [];

        $theme = $this->themeModel::create($themeConfig);

        $this->booted = false;
        $this->themes = [];
        $this->bootThemes();

        return $theme;
    }

    /**
     * Read configuration from Config/Meta file
     *
     * @param string $themeCode
     *
     * @return array|null
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function getMetaFromFile($path, $throw = true)
    {
        if (File::exists($metaPath = $path.'/theme.json')) {
            return json_decode(File::get($metaPath), true);
        }

        if ($throw) {
            throw new SystemException('Theme does not have a registration file in: '.$metaPath);
        }
    }

    public function getFileNameParts($path, Theme $theme)
    {
        $parts = explode('/', $path);
        $dirName = $parts[0];
        $fileName = implode('/', array_splice($parts, 1));

        $fileNameParts = $theme->onTemplate($dirName)->getFileNameParts($fileName);

        return [$dirName, implode('.', $fileNameParts)];
    }

    /**
     * Check configuration in Config file
     *
     * @param $config
     * @param string $code
     * @return array|null
     * @throws \Igniter\Flame\Exception\SystemException
     */
    protected function validateMetaFile($config, $code)
    {
        foreach ([
            'code',
            'name',
            'description',
            'author',
        ] as $item) {
            if (!array_key_exists($item, $config)) {
                throw new SystemException(sprintf(
                    Lang::get('igniter::system.missing.config_key'),
                    $item, $code
                ));
            }

            if ($item == 'code' && $code !== $config[$item]) {
                throw new SystemException(sprintf(
                    Lang::get('igniter::system.missing.config_code_mismatch'),
                    $config[$item], $code
                ));
            }
        }

        return $config;
    }

    protected function writeChildThemeMetaFile($path, $parentTheme, $themeConfig)
    {
        $themeConfig['parent'] = $parentTheme->name;
        $themeConfig['version'] = array_get($parentTheme->config, 'version');
        $themeConfig['author'] = array_get($parentTheme->config, 'author', '');
        $themeConfig['homepage'] = array_get($parentTheme->config, 'homepage', '');
        $themeConfig['require'] = $parentTheme->requires;

        if (File::isDirectory($path))
            throw new SystemException('Child theme path already exists.');

        File::makeDirectory($path, 0777, false, true);

        File::put($path.'/theme.json', json_encode($themeConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
