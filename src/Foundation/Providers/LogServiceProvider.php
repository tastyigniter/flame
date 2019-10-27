<?php namespace Igniter\Flame\Foundation\Providers;

use Illuminate\Log\LogServiceProvider as BaseLogServiceProvider;
use Psr\Log\LoggerInterface;

class LogServiceProvider extends BaseLogServiceProvider
{
    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Psr\Log\LoggerInterface $log
     *
     * @return void
     */
    protected function configureSingleHandler(LoggerInterface $log)
    {
        $log->useFiles(
            $this->app->storagePath().'/logs/system.log',
            $this->logLevel()
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Psr\Log\LoggerInterface $log
     *
     * @return void
     */
    protected function configureDailyHandler(LoggerInterface $log)
    {
        $log->useDailyFiles(
            $this->app->storagePath().'/logs/system.log', $this->maxFiles(),
            $this->logLevel()
        );
    }
}
