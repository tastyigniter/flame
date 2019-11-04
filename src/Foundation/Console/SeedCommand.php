<?php namespace Igniter\Flame\Foundation\Console;

use Illuminate\Database\Console\Seeds\SeedCommand as BaseSeedCommand;
use Symfony\Component\Console\Input\InputOption;

class SeedCommand extends BaseSeedCommand
{
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'System\\Database\\Seeds\\DatabaseSeeder'],

            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
