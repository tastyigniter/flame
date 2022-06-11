<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Support\Facades\File;
use Illuminate\Foundation\PackageManifest as BasePackageManifest;

class PackageManifest extends BasePackageManifest
{
    protected $metaFile = '/installed.json';

    public function packages()
    {
        return $this->getManifest();
    }

    public function extensions()
    {
        return collect($this->getManifest())->where('type', 'tastyigniter-extension')->all();
    }

    public function themes()
    {
        return collect($this->getManifest())->where('type', 'tastyigniter-theme')->all();
    }

    public function extensionConfig($key)
    {
        return collect($this->extensions())->flatMap(function ($configuration) use ($key) {
            return (array)($configuration[$key] ?? []);
        })->filter()->all();
    }

    public function themeConfig($key)
    {
        return collect($this->themes())->flatMap(function ($configuration) use ($key) {
            return (array)($configuration[$key] ?? []);
        })->filter()->all();
    }

    public function getVersion($code)
    {
        return collect($this->getManifest())->where('code', $code)->value('version');
    }

    public function coreVersion()
    {
        $packages = [];

        if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
            $installed = json_decode($this->files->get($path), true);
            $packages = $installed['packages'] ?? $installed;
        }

        return collect($packages)
            ->filter(function ($package) {
                return array_get($package, 'name') === 'tastyigniter/flame';
            })
            ->value('version');
    }

    public function build()
    {
        $packages = [];

        if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
            $installed = json_decode($this->files->get($path), true);
            $packages = $installed['packages'] ?? $installed;
        }

        $this->manifest = null;

        $this->write(collect($packages)
            ->filter(function ($package) {
                return array_has($package, 'extra.tastyigniter-extension') ||
                    array_has($package, 'extra.tastyigniter-theme');
            })
            ->mapWithKeys(function ($package) {
                if (array_get($package, 'extra.tastyigniter-extension', []))
                    return $this->formatExtension($package);

                if (array_get($package, 'extra.tastyigniter-theme', []))
                    return $this->formatTheme($package);
            })
            ->filter()
            ->all());
    }

    protected function formatExtension($package, $result = [])
    {
        if (!$autoload = array_get($package, 'autoload.psr-4', []))
            return $result;

        $namespace = key($autoload);
        $class = $namespace.'Extension';
        $directory = str_before(dirname(File::fromClass($class)), '/'.rtrim(current($autoload), '/'));
        $code = strtolower(str_replace('\\', '.', trim($namespace, '\\')));

        $json = json_decode(File::get($directory.'/composer.json'), true);
        $manifest = $json['extra']['tastyigniter-extension'] ?? [];

        $manifest['code'] = $code = array_get($manifest, 'code', $code);
        $manifest['type'] = 'tastyigniter-extension';
        $manifest['package_name'] = array_get($package, 'name');
        $manifest['version'] = array_get($package, 'version');
        $manifest['description'] = array_get($package, 'description');
        $manifest['author'] = array_get($package, 'authors.0.name');
        $manifest['homepage'] = array_get($package, 'homepage');
        $manifest['require'] = $this->formatRequire(array_get($package, 'require'));
        $manifest['namespace'] = $namespace;
        $manifest['extensionClass'] = $class;
        $manifest['directory'] = $directory;

        $result[$code] = $manifest;

        return $result;
    }

    protected function formatTheme($package, $result = [])
    {
        $directory = $this->vendorPath.'/composer/'.array_get($package, 'install-path');
        $json = json_decode(File::get($directory.'/composer.json'), true);
        $manifest = $json['extra']['tastyigniter-theme'] ?? [];

        $manifest['code'] = $code = array_get($manifest, 'code');
        $manifest['type'] = 'tastyigniter-theme';
        $manifest['package_name'] = array_get($package, 'name');
        $manifest['version'] = array_get($package, 'version');
        $manifest['description'] = array_get($package, 'description');
        $manifest['author'] = array_get($package, 'authors.0.name');
        $manifest['homepage'] = array_get($package, 'homepage');
        $manifest['publish'] = array_get($manifest, 'publish');
        $manifest['require'] = $this->formatRequire(array_get($package, 'require'));

        if (!array_key_exists('directory', $manifest))
            $manifest['directory'] = $directory;

        $result[$code] = $manifest;

        return $result;
    }

    protected function formatRequire($require)
    {
        return $require;
    }

    //
    //
    //

    public function installExtensions($extensions = null)
    {
        $installed = $this->installed();

        if (is_null($extensions))
            return array_get($installed, 'extensions', []);

        $installed['extensions'] = $extensions;
        $this->writeInstalled($installed);
    }

    public function installThemes($themes = null)
    {
        $installed = $this->installed();

        if (is_null($themes))
            return array_get($installed, 'themes', []);

        $installed['themes'] = $themes;
        $this->writeInstalled($installed);
    }

    public function installed()
    {
        $path = dirname($this->manifestPath).$this->metaFile;
        if (!is_file($path))
            return [];

        return json_decode($this->files->get($path, true), true) ?: [];
    }

    protected function writeInstalled($installed)
    {
        $this->files->replace(dirname($this->manifestPath).$this->metaFile, json_encode($installed));
    }
}
