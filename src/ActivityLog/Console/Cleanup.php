<?php

namespace Igniter\Flame\ActivityLog\Console;

use App;
use Carbon\Carbon;
use Igniter\Flame\ActivityLog\ActivityLogger;
use Illuminate\Console\Command;

class Cleanup extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'activitylog:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old records from the activity log.';

    public function handle()
    {
        $this->comment('Cleaning old activity log...');
        $maxAgeInDays = config('system.activityRecordsTTL', 365);
        $expiryDate = Carbon::now()->subDays($maxAgeInDays)->format('Y-m-d H:i:s');

        $activity = App::make(ActivityLogger::class)->getModelInstance();
        $amountDeleted = $activity::where('date_added', '<', $expiryDate)->delete();

        $this->info("Deleted {$amountDeleted} record(s) from the activity log.");
        $this->comment('All done!');
    }
}