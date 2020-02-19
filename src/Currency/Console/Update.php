<?php

namespace Igniter\Flame\Currency\Console;

use Illuminate\Console\Command;

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update exchange rates from an online source';
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        app('currency')->updateRates(TRUE);
    }
}