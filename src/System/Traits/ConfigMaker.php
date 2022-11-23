<?php

namespace Igniter\System\Traits;

use Igniter\Flame\Exception\SystemException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

trait ConfigMaker
{
    /**
     * @var array Specifies a path to the config directory.
     */
    public $configPath;

    protected $configFileExtension = '.php';

    /**
     * Reads the contents of the supplied file and applies it to this object.
     *
     * @param array $configFile
     * @param array $requiredConfig
     * @param null $index
     *
     * @return array
     */
    public function loadConfig($configFile = [], $requiredConfig = [], $index = null)
    {
        $config = $this->makeConfig($configFile, $requiredConfig);

        if (is_null($index))
            return $config;

        return $config[$index] ?? null;
    }

    /**
     * Reads the contents of the supplied file and applies it to this object.
     *
     * @param string|array $configFile
     * @param array $requiredConfig
     *
     * @return array
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function makeConfig($configFile, $requiredConfig = [])
    {
        if (!$configFile) {
            $configFile = [];
        }

        // Convert config to array
        if (is_object($configFile)) {
            $config = (array)$configFile;
        }
        // Embedded config
        elseif (is_array($configFile)) {
            $config = $configFile;
        }
        // Process config from file contents
        else {
            $configFile = $this->getConfigPath($configFile.$this->configFileExtension);

            if (!File::isFile($configFile)) {
                throw new SystemException(sprintf(
                    Lang::get('igniter::system.not_found.config'),
                    $configFile, get_called_class()
                ));
            }

            $config = File::getRequire($configFile);
        }

        // Validate required configuration
        foreach ($requiredConfig as $property) {
            if (!is_array($config) || !array_key_exists($property, $config)) {
                throw new SystemException(sprintf(
                    Lang::get('igniter::system.required.config'),
                    get_called_class(), $property
                ));
            }
        }

        return $config;
    }

    /**
     * Merges two configuration sources, either prepared or not, and returns
     * them as a single configuration object.
     *
     * @param $configLeft
     * @param $configRight
     *
     * @return array The config array
     */
    public function mergeConfig($configLeft, $configRight)
    {
        $configLeft = $this->makeConfig($configLeft);

        $configRight = $this->makeConfig($configRight);

        return array_merge($configLeft, $configRight);
    }

    /**
     * Locates a file based on it's definition. If the file starts with
     * the ~ symbol it will be returned in context of the application base path,
     * otherwise it will be returned in context of the config path.
     *
     * @param string $fileName File to load.
     * @param mixed $configPath Explicitly define a config path.
     *
     * @return string Full path to the config file.
     */
    public function getConfigPath($fileName, $configPath = null)
    {
        if (!$configPath)
            $configPath = $this->configPath;

        $fileName = File::symbolizePath($fileName);

        if (File::isLocalPath($fileName) || realpath($fileName) !== false)
            return $fileName;

        if (!is_array($configPath))
            $configPath = [$configPath];

        foreach ($configPath as $path) {
            $path = File::symbolizePath($path);
            $_fileName = $path.'/'.$fileName;
            if (File::isFile($_fileName)) {
                return $_fileName;
            }
        }

        return $fileName;
    }
}
