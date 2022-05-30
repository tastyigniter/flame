<?php

namespace Igniter\Admin\Jobs;

use Exception;
use Igniter\Admin\Classes\Allocator;
use Igniter\Admin\Models\AssignableLog;
use Igniter\Admin\Models\UserGroup;
use Igniter\Admin\Traits\Assignable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AllocateAssignable implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Igniter\Admin\Models\AssignableLog
     */
    public $assignableLog;

    /**
     * @var int
     */
    public $tries = 3;

    public function __construct(AssignableLog $assignableLog)
    {
        $this->assignableLog = $assignableLog->withoutRelations();
    }

    public function handle()
    {
        $lastAttempt = $this->attempts() >= $this->tries;

        try {
            if ($this->assignableLog->assignee_id)
                return;

            if (!in_array(Assignable::class, class_uses_recursive(get_class($this->assignableLog->assignable))))
                return;

            if (!$this->assignableLog->assignee_group instanceof UserGroup)
                return;

            Allocator::addSlot($this->assignableLog->getKey());

            if (!$assignee = $this->assignableLog->assignee_group->findAvailableAssignee())
                throw new Exception(lang('igniter::admin.user_groups.alert_no_available_assignee'));

            $this->assignableLog->assignable->assignTo($assignee);

            Allocator::removeSlot($this->assignableLog->getKey());

            return;
        }
        catch (Exception $exception) {
            if (!$lastAttempt) {
                $waitInSeconds = $this->waitInSecondsAfterAttempt($this->attempts());

                $this->release($waitInSeconds);
            }
        }

        if ($lastAttempt) {
            $this->delete();
        }
    }

    protected function waitInSecondsAfterAttempt(int $attempt)
    {
        if ($attempt > 3) {
            return 1000;
        }

        return 10 ** $attempt;
    }
}
