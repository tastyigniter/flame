<?php

namespace Igniter\System\Console\Commands;

use Igniter\Main\Classes\ThemeManager;
use Illuminate\Foundation\Console\VendorPublishCommand;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputOption;

class ThemeVendorPublish extends VendorPublishCommand
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'theme:vendor-publish';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Publish any publishable assets from themes';

    protected $signature;

    /**
     * The themes to publish.
     *
     * @var array
     */
    protected $themes = [];

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $this->determineWhatShouldBePublished();

        foreach ($this->themes as $theme) {
            $this->publishTheme($theme);
        }

        $this->info('Publishing complete.');
    }

    protected function determineWhatShouldBePublished()
    {
        if ($this->option('all')) {
            $this->themes = resolve(ThemeManager::class)->listThemes();
        }

        foreach ((array)$this->option('theme') as $theme) {
            $this->themes[$theme] = resolve(ThemeManager::class)->findTheme($theme);
        }
    }

    /**
     * Publishes the assets for a theme.
     *
     * @param \Igniter\Main\Classes\Theme $theme
     * @return void
     */
    protected function publishTheme($theme)
    {
        $published = false;

        $publishTo = public_path('vendor/'.$theme->getName());
        $pathsToPublish = $this->pathsToPublishFromTheme($theme);

        foreach ($pathsToPublish as $from) {
            $this->publishItem($from, $publishTo);

            $published = true;
        }

        if ($published === false) {
            $this->comment('No publishable resources for theme ['.$theme->getName().'].');
        }
    }

    /**
     * Get all of the paths to publish.
     *
     * @param \Igniter\Main\Classes\Theme $theme
     * @return array
     */
    protected function pathsToPublishFromTheme($theme)
    {
        $publishPath = array_get($theme->config, 'publish', []);
        if (!$publishPath && File::exists($theme->getAssetPath()))
            return [$theme->getAssetPath()];

        return array_map(function ($path) use ($theme) {
            return $theme->getPath().'/'.$path;
        }, $publishPath);
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['all', null, InputOption::VALUE_NONE, 'Publish assets for all themes without prompt.'],
            ['theme', null, InputOption::VALUE_OPTIONAL, 'One or many theme that have assets you want to publish.'],
            ['force', null, InputOption::VALUE_NONE, 'Force publish.'],
        ];
    }
}
