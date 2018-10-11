<?php

namespace Igniter\Flame\Location;

use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use InvalidArgumentException;

class WorkingPeriod
{
    const CLOSED = 'closed';

    const OPEN = 'open';

    const OPENING = 'opening';

    protected $date;

    protected $start;

    protected $end;

    protected $interval;

    protected $status;

    protected $disabled = FALSE;

    public function __construct($date, $start = null, $end = null, int $interval = 15)
    {
        $this->date = $this->parseDate($date)->startOfDay();
        $this->start = $this->parseStart($start);
        $this->end = $this->parseEnd($end);
        $this->interval = $interval < 1 ? 1 : $interval;
        $this->status = self::CLOSED;
    }

    public static function create($date, $start = null, $end = null, $interval = 15)
    {
        return new static($date, $start, $end, $interval);
    }

    /**
     * Get the start date & time.
     *
     * @return \Carbon\Carbon
     */
    public function date()
    {
        return $this->date;
    }

    /**
     * Get the start date & time.
     *
     * @return \Carbon\Carbon
     */
    public function start()
    {
        return $this->start;
    }

    /**
     * Get the end date & time.
     *
     * @return \Carbon\Carbon
     */
    public function end()
    {
        return $this->end;
    }

    public function interval()
    {
        return $this->interval;
    }

    public function status()
    {
        return $this->status;
    }

    public function disabled()
    {
        return $this->disabled;
    }

    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }

    public function openAllDay()
    {
        if (!$this->start OR !$this->end)
            return null;

        $diffInHours = $this->start()->diffInHours($this->end());

        return $diffInHours >= 23 OR $diffInHours == 0;
    }

    public function openLate()
    {
        return $this->start()->gt($this->end());
    }

    public function opening(Carbon $datetime)
    {
        return $this->start()->gte($datetime) AND $this->end()->gte($datetime);
    }

    public function timeslot()
    {
        $interval = new DateInterval('PT'.$this->interval.'M');
        $dateTimeslot = new DatePeriod($this->start(), $interval, $this->end());

        return collect($dateTimeslot);
    }

    /**
     * Return true if the Carbon instance passed as argument is between start
     * and end date & time.
     *
     * @param  Carbon $datetime
     * @return boolean
     */
    public function has(Carbon $datetime)
    {
        return $datetime->between($this->start(), $this->end());
    }

    public function check(Carbon $datetime)
    {
        return $this->status = $this->processCheck($datetime);
    }

    protected function parseDate($date)
    {
        if (!$date)
            return Carbon::now();

        if ($date instanceof DateTime)
            return Carbon::instance($date);

        if (is_string($date))
            return Carbon::parse($date);

        throw new InvalidArgumentException('The datetime must be an instance of DateTime or a valid datetime string.');
    }

    protected function parseStart($time)
    {
        if (is_null($time) OR !strlen($time))
            return null;

        return $this->date->copy()->setTimeFromTimeString($time);
    }

    protected function parseEnd($time)
    {
        if (is_null($time) OR !strlen($time))
            return null;

        $end = $this->date->copy()->setTimeFromTimeString($time);
        if ($this->openLate())
            $end->addDay();

        return $end;
    }

    protected function processCheck(Carbon $datetime)
    {
        if ($this->disabled())
            return self::CLOSED;

        if ($this->date()->isToday() AND $this->openAllDay())
            return self::OPEN;

        if ($this->has($datetime))
            return self::OPEN;

        if ($this->opening($datetime))
            return self::OPENING;

        return self::CLOSED;
    }
}