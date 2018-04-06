<?php namespace Igniter\Flame\Location;

use Carbon\Carbon;
use Igniter\Flame\Location\Models\Location;
use Igniter\Flame\Location\Models\WorkingHour;
use Illuminate\Support\Collection;

class WorkingSchedule
{
    /**
     * @var Collection
     */
    protected $hours;

    /**
     * @var
     */
    protected $workingType;

    /**
     * @var Carbon
     */
    protected $date;

    /**
     * @var WorkingHour
     */
    protected $currentPeriod;

    protected $daysInAdvance = 1;

    protected $periodsCache;

    /**
     * @param Collection $hours
     * @param $workingType
     *
     * @return self
     */
    public static function load(Collection $hours, $workingType)
    {
        $instance = new static;
        $instance->hours = $hours;
        $instance->workingType = $workingType;

        return $instance;
    }

    public function setDaysInAdvance($daysInAdvance)
    {
        $this->daysInAdvance = $daysInAdvance;

        return $this;
    }

    public function getWorkingType()
    {
        return $this->workingType;
    }

    public function isOpen($dateTime = null)
    {
        return $this->getStatus($dateTime) == WorkingHour::OPEN;
    }

    public function isOpening($dateTime = null)
    {
        return $this->getStatus($dateTime) == WorkingHour::OPENING;
    }

    public function isClosed($dateTime = null)
    {
        return $this->getStatus($dateTime) == WorkingHour::CLOSED;
    }

    public function getOpenTime($format = null)
    {
        return $this->getTime('open', null, $format);
    }

    public function getCloseTime($format = null)
    {
        return $this->getTime('close', null, $format);
    }

    public function getTime($name, $dateTime = null, $format = null)
    {
        if (!$period = $this->getPeriod($dateTime))
            return null;

        if (is_null($period->{$name})
            OR !$period->{$name} instanceof Carbon)
            return null;

        $time = $period->{$name};

        return $format ? $time->format($format) : $time;
    }

    /**
     * @param Carbon|int Date or timestamp
     *
     * @return string
     */
    public function getStatus($dateTime = null)
    {
        if (!$period = $this->getPeriod($dateTime))
            return WorkingHour::CLOSED;

        return $period->checkStatus($dateTime);
    }

    public function getPeriod($dateToCheck = null)
    {
        if (!is_null($dateToCheck) AND !$dateToCheck instanceof Carbon)
            $dateToCheck = make_carbon($dateToCheck);

        if (is_null($dateToCheck))
            $dateToCheck = Carbon::now();

        $dateIndex = $dateToCheck->toDateString();
        if (isset($this->periodsCache[$dateIndex]))
            return $this->periodsCache[$dateIndex];

        $startDate = $dateToCheck->isToday()
            ? $dateToCheck->copy()->subDay()
            : $dateToCheck->copy();

        $endDate = $dateToCheck->copy()->addDay($this->daysInAdvance);

        $dateRange = $this->createDateRange($startDate, $endDate);

        foreach ($dateRange as $dateString => $day) {
            if (!$workingHours = $this->getHoursByDay($day))
                continue;

            $carbonDate = make_carbon($dateString);
            $workingHours->setWeekDate($carbonDate);

            if ($workingHours->checkStatus($dateToCheck) != WorkingHour::CLOSED) {
                $dateIndex = $carbonDate->toDateString();
                return $this->periodsCache[$dateIndex] = $workingHours;
            }
        }

        return null;
    }

    public function generatePeriods($dateTime)
    {
        if (!is_null($dateTime) AND !$dateTime instanceof Carbon)
            $dateTime = make_carbon($dateTime);

        $startDate = $dateTime->copy();
        $endDate = $dateTime->copy()->addDay($this->daysInAdvance);
        $periods = $this->createDateRange($startDate, $endDate);

        $generated = [];
        foreach ($periods as $dateString => $day) {
            $workingHours = $this->getHoursByDay($day);

            if ($workingHours AND $workingHours->isEnabled()) {
                $newWorkingHours = clone $workingHours;

                $carbonDate = make_carbon($dateString);
                $newWorkingHours->setWeekDate($carbonDate);

                $generated[$dateString] = $newWorkingHours;
            }
        }

        return $generated;
    }

    public function generatePeriodsWithTimes($dateTime, $timeInterval)
    {
        $generated = [];
        foreach ($this->generatePeriods($dateTime) as $date => $workingHours) {
            $generated[$date] = [$workingHours, $workingHours->generateTimes($timeInterval)];
        }

        return $generated;
    }

    /**
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     *
     * @return \Illuminate\Support\Collection
     */
    protected function createDateRange(Carbon $startDate, Carbon $endDate)
    {
        $dates = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dateString = $date->toDateTimeString();
            $dayString = $date->format('N') - 1;

            // Use date string as array key to allow range to span over a week.
            $dates[$dateString] = $dayString;
        }

        return collect($dates);
    }

    /**
     * @return Collection
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * @param int $day Day of the week, 0 = Monday, 1 = Tuesday ...
     *
     * @return \Igniter\Flame\Location\Models\WorkingHour|null
     */
    public function getHoursByDay($day)
    {
        return $this->getHours()->get($day);
    }

    /**
     * @param Carbon|string $date
     *
     * @return \Igniter\Flame\Location\Models\WorkingHour|null
     */
    public function getHoursByDate(Carbon $date)
    {
        $day = $date->format('N') - 1;

        return $this->getHoursByDay($day);
    }
}