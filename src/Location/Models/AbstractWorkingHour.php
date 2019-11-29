<?php

namespace Igniter\Flame\Location\Models;

use Carbon\Carbon;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Location\Contracts;

abstract class AbstractWorkingHour extends Model implements Contracts\WorkingHourInterface
{
    const CLOSED = 'closed';

    const OPEN = 'open';

    const OPENING = 'opening';

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

    public function getWeekDate()
    {
        return new Carbon($this->day);
    }

    //
    // Accessors & Mutators
    //

    public function getDayAttribute()
    {
        return Carbon::now()->startOfWeek()->addDay($this->weekday);
    }

    public function getOpenAttribute()
    {
        $openDate = $this->getWeekDate();

        $openDate->setTimeFromTimeString($this->attributes['opening_time']);

        return $openDate;
    }

    public function getCloseAttribute()
    {
        $closeDate = $this->getWeekDate();

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

    public function isOpenAllDay()
    {
        if (!$this->open OR !$this->close)
            return null;

        $diffInHours = $this->open->diffInHours($this->close);

        return $diffInHours >= 23 OR $diffInHours == 0;
    }

    public function isPastMidnight()
    {
        if (!$this->opening_time OR !$this->closing_time)
            return null;

        return $this->opening_time > $this->closing_time;
    }

    public function getDay()
    {
        return $this->day->format('l');
    }

    public function getOpen()
    {
        return $this->open->format('H:i');
    }

    public function getClose()
    {
        return $this->close->format('H:i');
    }
}