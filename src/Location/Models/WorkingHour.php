<?php

namespace Igniter\Flame\Location\Models;

use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use Igniter\Flame\Database\Model;

class WorkingHour extends Model
{
    const CLOSED = 'closed';

    const OPEN = 'open';

    const OPENING = 'opening';

    protected static $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    /**
     * @var string The database table name
     */
    protected $table = 'working_hours';

    protected $primaryKey = 'location_id';

    public $incrementing = FALSE;

    protected $timeFormat = 'H:i';

    public $relation = [
        'belongsTo' => [
            'location' => ['Admin\Models\Locations_model'],
        ],
    ];

    public $casts = [
        'opening_time' => 'time',
        'closing_time' => 'time',
    ];

    protected $appends = ['day', 'open', 'close'];

    /**
     * @var Carbon
     */
    protected $weekDate;

    public function setWeekDate(Carbon $weekDate)
    {
        $this->weekDate = $weekDate;

        return $this;
    }

    public function getWeekDate()
    {
        if (is_null($this->weekDate))
            $this->weekDate = new Carbon("{$this->day}");

        return $this->weekDate;
    }

    public function setWeekDays($weekDays)
    {
        self::$weekDays = $weekDays;
    }

    public function getWeekDays()
    {
        return self::$weekDays;
    }

    //
    // Accessors & Mutators
    //

    public function getDayAttribute()
    {
        return self::$weekDays[$this->weekday];
    }

    public function getOpenAttribute()
    {
        $openDate = $this->getWeekDate()->copy();

        $openDate->setTimeFromTimeString($this->attributes['opening_time']);

        return $openDate;
    }

    public function getCloseAttribute()
    {
        $closeDate = $this->getWeekDate()->copy();

        $closeDate->setTimeFromTimeString($this->attributes['closing_time']);

        if ($this->isPastMidnight())
            $closeDate->addDay();

        return $closeDate;
    }

    //
    // Helpers
    //

    public function isEnabled()
    {
        return $this->status == 1;
    }

    public function isOpen()
    {
        return $this->checkStatus() == self::OPEN;
    }

    public function isOpening()
    {
        return $this->checkStatus() == self::OPENING;
    }

    public function isClosed()
    {
        return $this->checkStatus() == self::CLOSED;
    }

    public function isOpenAllDay()
    {
        if (!$this->opening_time OR !$this->closing_time)
            return null;

        $diffInHours = $this->opening_time->diffInHours($this->close);

        return $diffInHours >= 23 OR $diffInHours == 0;
    }

    public function isPastMidnight()
    {
        if (!$this->opening_time OR !$this->closing_time)
            return null;

        return $this->opening_time->gt($this->closing_time);
    }

    public function checkStatus(Carbon $dateTime = null)
    {
        if (is_null($dateTime))
            $dateTime = Carbon::now();

        if (!$this->status)
            return self::CLOSED;

        if ($this->getWeekDate()->isToday() AND $this->isOpenAllDay())
            return self::OPEN;

        if ($this->close->gte($dateTime))
            return $this->open->lte($dateTime) ? self::OPEN : self::OPENING;

        return self::CLOSED;
    }

    public function generateTimes($interval)
    {
        $interval = new DateInterval("PT".$interval."M");
        $dateTimes = new DatePeriod($this->open, $interval, $this->close);

        return $dateTimes;
    }
}