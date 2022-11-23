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
        $this->components->info('Discovering addons');

        $manifest->build();

        collect($manifest->packages())
            ->keys()
            ->each(fn($description) => $this->components->task($description))
            ->whenNotEmpty(fn() => $this->newLine());
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
