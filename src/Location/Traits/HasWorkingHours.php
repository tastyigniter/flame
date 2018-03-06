<?php

namespace Igniter\Flame\Location\Traits;

use Carbon\Carbon;
use Exception;
use Igniter\Flame\Location\WorkingSchedule;

trait HasWorkingHours
{
    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $workingHours;

    /**
     * @var WorkingSchedule
     */
    protected $workingSchedules;

    public function availableWorkingTypes()
    {
        return [static::OPENING, static::DELIVERY, static::COLLECTION];
    }

    public function listWorkingHours()
    {
        if (!$this->workingHours)
            $this->workingHours = $this->getWorkingHours();

        return $this->workingHours;
    }

    public function workingHourType($hourType = null)
    {
        return array_get($this->options, "hours.{$hourType}.type");
    }

    public function getWorkingHoursByType($type)
    {
        if (!$workingHours = $this->listWorkingHours())
            return null;

        return $workingHours->groupBy('type')->get($type);
    }

    public function getWorkingHoursByDay($day)
    {
        if (!$workingHours = $this->listWorkingHours())
            return null;

        return $workingHours->groupBy('day')->get($day);
    }

    public function getWorkingHours()
    {
        if (!$this->hasRelation('working_hours'))
            throw new Exception(sprintf("Model '%s' does not contain a definition for 'working_hours'.",
                get_class($this)));

        return $this->working_hours()->get();
    }

    public function workingScheduleInstance($type = null)
    {
        if (is_null($type) OR !in_array($type, $this->availableWorkingTypes()))
            throw new Exception("Defined parameter '$type' is not a valid working type.");

        if (isset($this->workingSchedules[$type]))
            return $this->workingSchedules[$type];

        $workingSchedule = new WorkingSchedule($this, $type);
        $workingSchedule->setDate(Carbon::today());

        $this->workingSchedules[$type] = $workingSchedule;

        return $workingSchedule;
    }
}