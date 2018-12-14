<?php namespace Igniter\Flame\Location;

use Igniter\Flame\Location\Exceptions\WorkingHourException;

class WorkingRange
{
    /** @var \Igniter\Flame\Location\WorkingTime */
    protected $start;

    /** @var \Igniter\Flame\Location\WorkingTime */
    protected $end;

    protected function __construct(WorkingTime $start, WorkingTime $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public static function create(array $times): self
    {
        list($start, $end) = $times;

        return new static(
            WorkingTime::create($start),
            WorkingTime::create($end)
        );
    }

    /**
     * @param \Igniter\Flame\Location\WorkingRange[] $ranges
     * @return \Igniter\Flame\Location\WorkingRange
     * @throws \Igniter\Flame\Location\Exceptions\WorkingHourException
     */
    public static function fromRanges(array $ranges): self
    {
        if (count($ranges) === 0)
            throw new WorkingHourException('The given ranges must contain at least one range.');

        array_walk($ranges, function ($range) {
            if (!$range instanceof self)
                throw new WorkingHourException('The given ranges is not a valid list of TimeRange instance containing.');
        });

        $start = $ranges[0]->start();
        $end = $ranges[0]->end();

        foreach (array_slice($ranges, 1) as $range) {
            $rangeStart = $range->start();
            if ($rangeStart->format('Gi') < $start->format('Gi'))
                $start = $rangeStart;

            $rangeEnd = $range->end();
            if ($rangeEnd->format('Gi') > $end->format('Gi'))
                $end = $rangeEnd;
        }

        return new self($start, $end);
    }

    public function start(): WorkingTime
    {
        return $this->start;
    }

    public function end(): WorkingTime
    {
        return $this->end;
    }

    public function endsNextDay(): bool
    {
        return $this->end->isBefore($this->start);
    }

    public function opensAllDay(): bool
    {
        $diffInHours = $this->start()->diff($this->end());

        return $diffInHours >= 23 OR $diffInHours == 0;
    }

    public function containsTime(WorkingTime $time): bool
    {
        if ($this->endsNextDay()) {
            if ($time->isSameOrAfter($this->start)) {
                return $time->isAfter($this->end);
            }

            return $time->isBefore($this->end);
        }

        return $time->isSameOrAfter($this->start) AND $time->isBefore($this->end);
    }

    public function overlaps(self $timeRange): bool
    {
        return $this->containsTime($timeRange->start) OR $this->containsTime($timeRange->end);
    }

    public function format(string $timeFormat = 'H:i', string $rangeFormat = '%s-%s'): string
    {
        return sprintf($rangeFormat, $this->start->format($timeFormat), $this->end->format($timeFormat));
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
