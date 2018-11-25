<?php

namespace Igniter\Flame\Location\Models;

use Carbon\Carbon;
use Igniter\Flame\Database\Model;

class WorkingHour extends Model
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

    protected $weekDate;

    public function getWeekDate()
    {
        if (is_null($this->weekDate))
            $this->weekDate = new Carbon("{$this->day}");

        return $this->weekDate;
    }

    //
    // Accessors & Mutators
    //

    public function getDayAttribute()
    {
        return Carbon::now()->startOfWeek()->addDay($this->weekday)->format('l');
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
}