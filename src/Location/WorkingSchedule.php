<?php namespace Igniter\Flame\Location;

use Carbon\Carbon;
use Igniter\Flame\Location\Models\Location;
use Igniter\Flame\Location\Models\WorkingHour;

class WorkingSchedule
{
    /**
     * @var Location
     */
    protected $location;

    /**
     * @var
     */
    protected $type;

    /**
     * @var Carbon
     */
    protected $date;

    /**
     * @var WorkingHour
     */
    protected $currentPeriod;

    public static function load(Location $location, $type, $today)
    {
        $instance = new static;
        $instance->location = $location;
        $instance->type = $type;
        $instance->setDate($today);

        return $instance;
    }

    public function setDate(Carbon $date)
    {
        $this->date = $date;

        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getType()
    {
        return $this->type;
    }

    public function currentPeriod()
    {
        if (is_null($this->currentPeriod))
            $this->currentPeriod = $this->getPeriod($this->date);

        return $this->currentPeriod;
    }

    public function isOpen()
    {
        if (!$currentPeriod = $this->currentPeriod())
            return FALSE;

        return $currentPeriod->isOpen();
    }

    public function isOpening()
    {
        if (!$currentPeriod = $this->currentPeriod())
            return FALSE;

        return $currentPeriod->isOpening();
    }

    public function isClosed()
    {
        if (!$currentPeriod = $this->currentPeriod())
            return FALSE;

        return $currentPeriod->isClosed();
    }

    public function getOpenTime($format = null)
    {
        return $this->getTime('open', $format);
    }

    public function getCloseTime($format = null)
    {
        return $this->getTime('close', $format);
    }

    public function getTime($name, $format = null)
    {
        if (!$currentPeriod = $this->currentPeriod())
            return null;

        if (is_null($currentPeriod->{$name})
            OR !$currentPeriod->{$name} instanceof Carbon)
            return null;

        $time = $currentPeriod->{$name};

        return $format ? $time->format($format) : $time;
    }

    /**
     * @param Carbon|int Date or timestamp
     *
     * @return string
     */
    public function getStatus($dateTime = null)
    {
        if (!$currentPeriod = $this->currentPeriod())
            return WorkingHour::CLOSED;

        if (!is_null($dateTime) AND !$dateTime instanceof Carbon)
            $dateTime = make_carbon($dateTime);

        return $currentPeriod->checkStatus($dateTime);
    }

    public function getPeriod(Carbon $dateToCheck, $daysInAdvance = 1)
    {
        $startDate = $dateToCheck->isToday()
            ? $dateToCheck->copy()->subDay()
            : $dateToCheck->copy();

        $endDate = $dateToCheck->copy()->addDay($daysInAdvance);

        $dateRange = $this->createDateRange($startDate, $endDate);

        foreach ($dateRange as $date => $day) {
            if (!$workingHours = $this->getHoursByDay($day))
                continue;

            $workingHours->setWeekDate(make_carbon($date));

            if (!$workingHours->isClosed())
                return $workingHours;
        }

        return null;
    }

    public function generatePeriods($daysInAdvance)
    {
        $startDate = $this->date->copy();
        $endDate = $this->date->copy()->addDay($daysInAdvance);
        $periods = $this->createDateRange($startDate, $endDate);

        $generated = [];
        foreach ($periods as $date => $day) {
            if ($workingHours = $this->getHoursByDay($day)) {
                $newWorkingHours = clone $workingHours;
                $newWorkingHours->setWeekDate(make_carbon($date));

                $generated[$date] = $newWorkingHours;
            }
        }

        return $generated;
    }

    public function generatePeriodsWithTimes($daysInAdvance, $timeInterval)
    {
        $generated = [];
        foreach ($this->generatePeriods($daysInAdvance) as $date => $workingHours) {
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
            $dateString = $date->format('Y-m-d');
            $dayString = $date->format('N') - 1;
            $dates[$dateString] = $dayString;
        }

        return collect($dates);
    }

    /**
     * @return \Igniter\Flame\Location\Models\WorkingHour|null
     */
    public function getHours()
    {
        return $this->location->getWorkingHoursByType($this->getType());
    }

    /**
     * @param Carbon|string $date
     *
     * @return \Igniter\Flame\Location\Models\WorkingHour|null
     */
    public function getHoursByDate($date)
    {
        if (!$date instanceof Carbon)
            $date = make_carbon($date);

        $day = $date->format('N') - 1;

        return $this->getHoursByDay($day);
    }

    /**
     * @param int $day Day of the week
     *
     * @return \Igniter\Flame\Location\Models\WorkingHour|null
     */
    public function getHoursByDay($day)
    {
        $workingHours = $this->getHours();

        if (!isset($workingHours[$day]))
            return null;

        return $workingHours[$day];
    }
}