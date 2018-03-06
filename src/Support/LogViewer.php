<?php

namespace Igniter\Flame\Support;

use Exception;

/**
 * Class LogViewer
 * Based on Rap2hpoutre\LaravelLogViewer
 * @package Igniter\Flame\Support
 */
class LogViewer
{
    // Limit to 30MB, reading larger files can eat up memory
    const MAX_FILE_SIZE = 31457280;

    /**
     * @var string file
     */
    protected static $file;

    protected static $levelClasses = [
        'debug'     => 'info',
        'info'      => 'info',
        'notice'    => 'info',
        'warning'   => 'warning',
        'error'     => 'danger',
        'critical'  => 'danger',
        'alert'     => 'danger',
        'emergency' => 'danger',
        'processed' => 'info',
    ];

    protected static $levelIcons = [
        'debug'     => 'info',
        'info'      => 'info',
        'notice'    => 'info',
        'warning'   => 'warning',
        'error'     => 'warning',
        'critical'  => 'warning',
        'alert'     => 'warning',
        'emergency' => 'warning',
        'processed' => 'info',
    ];

    /**
     * Log levels that are used
     * @var array
     */
    protected static $levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
        'processed',
    ];

    /**
     * @param string $file
     *
     * @throws \Exception
     */
    public static function setFile($file)
    {
        self::$file = self::pathToLogFile($file);
    }

    /**
     * @param string $file
     *
     * @return string
     * @throws \Exception
     */
    public static function pathToLogFile($file)
    {
        $logsPath = storage_path('logs');
        if (app('files')->exists($file)) { // try the absolute path
            return $file;
        }

        $file = $logsPath.'/'.$file;
        // check if requested file is really in the logs directory
        if (dirname($file) !== $logsPath) {
            throw new Exception('No such log file');
        }

        return $file;
    }

    /**
     * @return string
     */
    public static function getFileName()
    {
        return basename(self::$file);
    }

    /**
     * @return array
     */
    public static function all()
    {
        $log = [];
        $pattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\].*/';
        if (!self::$file) {
            $logFile = self::getFiles();

            if (!count($logFile))
                return [];

            self::$file = $logFile[0];
        }

        if (app('files')->size(self::$file) > self::MAX_FILE_SIZE)
            return null;

        $file = app('files')->get(self::$file);
        preg_match_all($pattern, $file, $headings);

        if (!is_array($headings))
            return $log;

        $logData = preg_split($pattern, $file);

        if ($logData[0] < 1) {
            array_shift($logData);
        }

        foreach ($headings as $h) {
            for ($i = 0, $j = count($h); $i < $j; $i++) {
                foreach (self::$levels as $level) {
                    if (strpos(strtolower($h[$i]), '.'.$level)
                        OR strpos(strtolower($h[$i]), $level.':')
                    ) {

                        preg_match(
                            '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\](?:.*?(\w+)\.|.*?)'
                            .$level.': (.*?)( in .*?:[0-9]+)?$/i', $h[$i], $current
                        );

                        if (!isset($current[3])) continue;

                        $log[] = [
                            'context' => $current[2],
                            'level'   => strtoupper($level),
                            'class'   => self::$levelClasses[$level],
                            'icon'    => self::$levelIcons[$level],
                            'date'    => $current[1],
                            'text'    => $current[3],
                            'summary' => isset($current[4]) ? $current[4] : null,
                            'stack'   => preg_replace("/^\n*/", '', $logData[$i]),
                        ];
                    }
                }
            }
        }

        return array_reverse($log);
    }

    /**
     * @param bool $basename
     *
     * @return array
     */
    public static function getFiles($basename = FALSE)
    {
        $files = glob(storage_path().'/logs/*.log');
        $files = array_reverse($files);
        $files = array_filter($files, 'is_file');

        if ($basename && is_array($files)) {
            foreach ($files as $k => $file) {
                $files[$k] = basename($file);
            }
        }

        return array_values($files);
    }
}