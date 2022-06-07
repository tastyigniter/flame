<?php

namespace Igniter\System\Console\Commands;

use Igniter\System\Classes\PackageManifest;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class IgniterPackageDiscover extends Command
{
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'igniter:package-discover';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Rebuild the cached addons manifest.';

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle(PackageManifest $manifest)
    {
        $manifest->build();

        foreach (array_keys($manifest->packages()) as $package) {
            $this->line("Discovered Addon: <info>{$package}</info>");
        }

        $this->info('Addon manifest generated successfully.');
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run.'],
        ];
    }
}
