<?php

namespace Igniter\Flame\Location\Traits;

use Carbon\Carbon;
use Exception;
use Igniter\Flame\Location\WorkingSchedule;
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

    public function getWorkingHours()
    {
        if (!$this->hasRelation('working_hours'))
            throw new Exception(sprintf("Model '%s' does not contain a definition for 'working_hours'.",
                get_class($this)));

        return $this->working_hours()->get();
    }

    public function workingSchedule($type = null, $date = null)
    {
        if (is_null($type) OR !in_array($type, $this->availableWorkingTypes()))
            throw new InvalidArgumentException("Defined parameter '$type' is not a valid working type.");

        if (isset($this->workingSchedules[$type]))
            return $this->workingSchedules[$type];

        $hours = $this->getWorkingHoursByType($type);
        $workingSchedule = WorkingSchedule::load($hours, $type);

        $this->workingSchedules[$type] = $workingSchedule;

        return $workingSchedule;
    }
}