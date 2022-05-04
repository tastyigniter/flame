<?php

namespace Igniter\Flame\Location;

use ArrayAccess;
use ArrayIterator;
use Countable;
use DateInterval;
use DateTimeInterface;
use Igniter\Flame\Location\Exceptions\WorkingHourException;
use IteratorAggregate;

class WorkingPeriod implements ArrayAccess, Countable, IteratorAggregate
{
    const CLOSED = 'closed';

    const OPEN = 'open';

    const OPENING = 'opening';

    /**
     * @var \Igniter\Flame\Location\WorkingRange[]
     */
    protected $ranges = [];

    public static function create($times)
    {
        $period = new static();

        $timeRanges = array_map(function ($times) {
            return WorkingRange::create($times);
        }, $times);

        $period->checkWorkingRangesOverlaps($timeRanges);

        $period->ranges = $timeRanges;

        return $period;
    }

    public function isOpenAt(WorkingTime $time)
    {
        return !is_null($this->findTimeInRange($time));
    }

    public function openTimeAt(WorkingTime $time)
    {
        if ($range = $this->findTimeInRange($time))
            return $range->start();

        return optional(current($this->ranges))->start();
    }

    public function closeTimeAt(WorkingTime $time)
    {
        if ($range = $this->findTimeInRange($time))
            return $range->end();

        return optional(end($this->ranges))->end();
    }

    /**
     * @param \Igniter\Flame\Location\WorkingTime $time
     * @return bool|\Igniter\Flame\Location\WorkingTime
     */
    public function nextOpenAt(WorkingTime $time)
    {
        foreach ($this->ranges as $range) {
            if ($range->containsTime($time)) {
                if (count($this->ranges) === 1) {
                    return $range->start();
                }
                if (next($range) !== $range && $nextOpenTime = next($range)) {
                    reset($range);

                    return $nextOpenTime;
                }
            }

            if ($nextOpenTime = $this->findNextTimeInFreeTime('start', $time, $range)) {
                reset($range);

                return $nextOpenTime;
            }
        }

        return false;
    }

    /**
     * @param \Igniter\Flame\Location\WorkingTime $time
     * @return bool|\Igniter\Flame\Location\WorkingTime
     */
    public function nextCloseAt(WorkingTime $time)
    {
        foreach ($this->ranges as $range) {
            if ($range->containsTime($time) && $nextCloseTime = $range->end()) {
                return $nextCloseTime;
            }

            if ($nextCloseTime = $this->findNextTimeInFreeTime('end', $time, $range)) {
                return $nextCloseTime;
            }
        }

        return false;
    }

    public function opensAllDay()
    {
        $diffInHours = 0;
        foreach ($this->ranges as $range) {
            $interval = $range->start()->diff($range->end());
            $diffInHours += (int)$interval->format('%H');
        }

        return $diffInHours >= 23 || $diffInHours == 0;
    }

    public function closesLate()
    {
        foreach ($this->ranges as $range) {
            if ($range->endsNextDay())
                return true;
        }

        return false;
    }

    public function opensLateAt(WorkingTime $time)
    {
        foreach ($this->ranges as $range) {
            if ($range->endsNextDay() && $range->containsTime($time))
                return true;
        }

        return false;
    }

    public function timeslot(DateTimeInterface $dateTime, DateInterval $interval, ?DateInterval $leadTime = null)
    {
        return WorkingTimeslot::make($this->ranges)->generate(
            $dateTime, $interval, $leadTime
        );
    }

    protected function findTimeInRange(WorkingTime $time)
    {
        foreach ($this->ranges as $range) {
            if ($range->containsTime($time))
                return $range;
        }
    }

    protected function findNextTimeInFreeTime($type, WorkingTime $time, WorkingRange $timeRange, WorkingRange &$prevTimeRange = null)
    {
        $timeOffRange = $prevTimeRange
            ? WorkingRange::create([$prevTimeRange->end(), $timeRange->start()])
            : WorkingRange::create(['00:00', $timeRange->start()]);

        if (
            $timeOffRange->containsTime($time)
            || $timeOffRange->start()->isSame($time)
        ) return $timeRange->{$type}();

        $prevTimeRange = $timeRange;
    }

    /**
     * @param \Igniter\Flame\Location\WorkingRange[] $ranges
     * @throws \Igniter\Flame\Location\Exceptions\WorkingHourException
     */
    protected function checkWorkingRangesOverlaps($ranges)
    {
        foreach ($ranges as $index => $range) {
            $nextRange = $ranges[$index + 1] ?? null;
            if ($nextRange && $range->overlaps($nextRange)) {
                throw new WorkingHourException(sprintf(
                    'Time ranges %s and %s overlap.',
                    $range, $nextRange
                ));
            }
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->ranges);
    }

    /**
     * Retrieve an external iterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->ranges);
    }

    /**
     * Count elements of an object
     * @link https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->ranges);
    }

    /**
     * Whether a offset exists
     *
     * @param mixed $offset
     * @return bool true on success or false on failure.
     */
    public function offsetExists($offset)
    {
        return isset($this->ranges[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @param mixed $offset
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->ranges[$offset];
    }

    /**
     * Offset to set
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        throw new WorkingHourException('Can not set ranges');
    }

    /**
     * Offset to unset
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->ranges[$offset]);
    }

    public function __toString()
    {
        $values = array_map(function ($range) {
            return (string)$range;
        }, $this->ranges);

        return implode(',', $values);
    }
}
