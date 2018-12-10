<?php

namespace Igniter\Flame\Location\Traits;

use Carbon\Carbon;
use Exception;
use Igniter\Flame\Location\WorkingSchedule;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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

    protected $currentTime;

    /**
     * @return Carbon
     */
    public function getCurrentTime()
    {
        if (!is_null($this->currentTime))
            return $this->currentTime;

        return $this->currentTime = Carbon::now();
    }

    public function availableWorkingTypes()
    {
        return [static::OPENING, static::DELIVERY, static::COLLECTION];
    }

    public function listWorkingHours()
    {
        if (!$this->workingHours)
            $this->workingHours = $this->loadWorkingHours();

        return $this->workingHours;
    }

    /**
     * @param null $hourType
     *
     * @return mixed 24_7, daily or flexible
     */
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

    public function getWorkingHoursByDay($weekday)
    {
        if (!$workingHours = $this->listWorkingHours())
            return null;

        return $workingHours->groupBy('weekday')->get($weekday);
    }

    public function getWorkingHourByDayAndType($weekday, $type)
    {
        if (!$workingHours = $this->getWorkingHoursByDay($weekday))
            return null;

        return $workingHours->groupBy('type')->get($type)->first();
    }

    public function getWorkingHourByDateAndType($date, $type)
    {
        if (!$date instanceof Carbon)
            $date = make_carbon($date);

        $weekday = $date->format('N') - 1;

        return $this->getWorkingHourByDayAndType($weekday, $type);
    }

    public function loadWorkingHours()
    {
        if (!$this->hasRelation('working_hours'))
            throw new Exception(sprintf("Model '%s' does not contain a definition for 'working_hours'.",
                get_class($this)));

        return $this->working_hours()->get();
    }

    public function newWorkingSchedule($type, $days = null, $interval = null)
    {
        $types = $this->availableWorkingTypes();
        if (is_null($type) OR !in_array($type, $types))
            throw new InvalidArgumentException("Defined parameter '$type' is not a valid working type.");

        if (is_null($days)) {
            $days = $this->hasFutureOrder()
                ? (int)$this->futureOrderDays($type)
                : 0;
        }

        $schedule = WorkingSchedule::create(
            $this->getWorkingHoursByType($type) ?? new Collection([]),
            $days, $interval ?? $this->getOrderTimeInterval($type)
        );

        $schedule->setNow($this->getCurrentTime());

        return $schedule;
    }
}