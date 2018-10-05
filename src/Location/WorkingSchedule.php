<?php namespace Igniter\Flame\Location;

use Carbon\Carbon;
use DateTime;
use Igniter\Flame\Location\Models\WorkingHour;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class WorkingSchedule
{
    protected static $workingSchedulesCache = [];

    /**
     * @var Collection Holds WorkingHour models
     */
    protected $hours;

    /**
     * @var Collection Holds working periods
     */
    protected $periods;

    /**
     * @var Carbon
     */
    protected $now;

    /**
     * @param Collection $hours
     * @param int $days
     * @param int $interval
     * @return void
     */
    public function __construct(Collection $hours, $days = 0, $interval = 15)
    {
        $this->hours = $hours;
        $this->days = $days;
        $this->interval = $interval;
    }

    /**
     * @param $hours
     * @param $days
     * @param $interval
     * @return self
     */
    public static function create($hours, $days, $interval)
    {
        return new static($hours, $days, $interval);
    }

    public function setNow(Carbon $now)
    {
        $this->now = $now;

        return $this;
    }

    public function isOpen()
    {
        return $this->checkStatus() === WorkingHour::OPEN;
    }

    public function isOpening()
    {
        return $this->checkStatus() === WorkingHour::OPENING;
    }

    public function isClosed()
    {
        return $this->checkStatus() === WorkingHour::CLOSED;
    }

    public function getOpenTime($format = null)
    {
        $time = $this->getTime('start');

        return ($time AND $format) ? $time->format($format) : $time;
    }

    public function getCloseTime($format = null)
    {
        $time = $this->getTime('end');

        return ($time AND $format) ? $time->format($format) : $time;
    }

    /**
     * @param Carbon|mixed Date or timestamp
     *
     * @return string
     */
    public function checkStatus($datetime = null)
    {
        return $this->getPeriod($datetime)->status();
    }

    /**
     * @param Carbon|mixed $datetime
     * @return WorkingPeriod
     */
    public function getPeriod($datetime = null)
    {
        $datetime = $this->parseDate($datetime);

        $periods = $this->getPeriods($datetime);

        $period = $periods->first(function (WorkingPeriod $period) use ($datetime) {
            return $period->check($datetime) != WorkingHour::CLOSED;
        });

        return $period ?? WorkingPeriod::create($datetime);
    }

    /**
     * @return Collection
     */
    public function getTimeslot()
    {
        $timeslot = $this->getPeriods()->mapWithKeys(function (WorkingPeriod $period, $date) {
            $now = $this->now->copy()->addMinutes($period->interval());
            if ($period->check($now) == WorkingHour::CLOSED)
                return [];

            $timeslot = $period->timeslot()->filter(function ($slot) use ($now) {
                return $now->lte($slot);
            })->values();

            return [$date => $timeslot];
        });

        return $timeslot->collapse();
    }

    /**
     * @return Collection
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * @param $name
     * @param null $datetime
     * @return Carbon
     */
    protected function getTime($name, $datetime = null)
    {
        $period = $this->getPeriod($datetime);
        if ($period AND in_array($name, ['start', 'end']))
            return $period->$name();

        throw new InvalidArgumentException(sprintf('The $name must be a valid method on %s.', get_class($period)));
    }

    protected function getPeriods(Carbon $datetime = null)
    {
        $datetime = $this->parseDate($datetime);
        $datetimeStr = $datetime->toDateString();

        if (isset($this->periods[$datetimeStr]))
            return $this->periods[$datetimeStr];

        $periods = $this->getRangeOfDays($datetime)->map(function ($day, $date) {
            if (!$hourModel = $this->getHours()->get($day))
                return FALSE;

            $start = $hourModel->opening_time;
            $end = $hourModel->closing_time;
            $interval = $this->interval;
            $period = WorkingPeriod::create($date, $start, $end, $interval);
            $period->setDisabled(!$hourModel->isEnabled());

            return $period;
        })->filter();

        $this->periods[$datetimeStr] = $periods;

        return $periods;
    }

    /**
     * @param \Carbon\Carbon $startDate
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRangeOfDays(Carbon $startDate)
    {
        $startDate = $startDate->copy()->startOfDay();
        $start = $startDate->copy()->subDay();
        $end = $startDate->copy()->addDay($this->days);

        $dates = [];
        for ($date = $start; $date->lte($end); $date->addDay()) {
            // Use date string as array key to allow range to span over a week.
            $dates[$date->toDateTimeString()] = $date->format('N') - 1;
        }

        return collect($dates);
    }

    protected function parseDate($start)
    {
        if (!$start) {
            return $this->now ?? Carbon::now();
        }
        if ($start instanceof DateTime) {
            return Carbon::instance($start);
        }
        if (is_string($start)) {
            return Carbon::parse($start);
        }

        throw new InvalidArgumentException('The datetime must be an instance of DateTime or a valid datetime string.');
    }
}
