<?php

namespace Igniter\Flame\Location;

use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;

class WorkingSchedule
{
    protected $type;

    protected $timezone;

    /**
     * @var \Igniter\Flame\Location\WorkingPeriod[] Holds working periods
     */
    protected $periods = [];

    /**
     * @var \Igniter\Flame\Location\WorkingPeriod[] Holds working periods exceptions
     */
    protected $exceptions = [];

    protected $minDays;

    protected $maxDays;

    /**
     * @param null $timezone
     * @param int|array $days
     */
    public function __construct($timezone = null, $days = 5)
    {
        $this->timezone = $timezone ? new DateTimeZone($timezone) : null;
        [$this->minDays, $this->maxDays] = is_array($days) ? $days : [0, (int)$days];

        $this->periods = WorkingDay::mapDays(function () {
            return new WorkingPeriod;
        });
    }

    /**
     * @param $days
     * @param $periods
     * @param array $exceptions
     * @return self
     *
     * $periods = [
     *    [
     *      'day' => 'monday',
     *      'open' => '09:00',
     *      'close' => '12:00'
     *    ],
     *    [
     *      'day' => 'monday',
     *      'open' => '09:00',
     *      'close' => '12:00'
     *    ],
     *    'wednesday' => [
     *      ['09:00', '12:00'],
     *      ['09:00', '12:00']
     *    ]
     * ];
     */
    public static function create($days, $periods, $exceptions = [])
    {
        return (new static(null, $days))->fill([
            'periods' => $periods,
            'exceptions' => $exceptions,
        ]);
    }

    public function fill($data)
    {
        $exceptions = Arr::get($data, 'exceptions', []);
        $periods = $this->parsePeriods(Arr::get($data, 'periods', []));

        $this->setPeriods($periods);
        $this->setExceptions($exceptions);

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function setNow(DateTime $now)
    {
        traceLog('Deprecated function. No longer supported.');

        return $this;
    }

    public function setTimezone($timezone)
    {
        $this->timezone = new DateTimeZone($timezone);
    }

    public function getType()
    {
        return $this->type;
    }

    public function minDays()
    {
        return $this->minDays;
    }

    public function days()
    {
        return $this->maxDays;
    }

    public function exceptions(): array
    {
        return $this->exceptions;
    }

    //
    //
    //

    /**
     * @param string $day
     * @return \Igniter\Flame\Location\WorkingPeriod
     * @throws \Igniter\Flame\Location\Exceptions\WorkingHourException
     */
    public function forDay(string $day): WorkingPeriod
    {
        $day = WorkingDay::normalizeName($day);

        return $this->periods[$day];
    }

    /**
     * @param \DateTimeInterface $date
     * @return \Igniter\Flame\Location\WorkingPeriod
     */
    public function forDate(DateTimeInterface $date): WorkingPeriod
    {
        $date = $this->applyTimezone($date);

        return $this->exceptions[$date->format('Y-m-d')]
            ?? ($this->exceptions[$date->format('m-d')]
                ?? $this->forDay(WorkingDay::onDateTime($date)));
    }

    public function isOpen()
    {
        return $this->isOpenAt(new DateTime());
    }

    public function isOpening()
    {
        return $this->nextOpenAt(new DateTime()) ? true : false;
    }

    public function isClosed()
    {
        return $this->isClosedAt(new DateTime());
    }

    public function isOpenOn(string $day): bool
    {
        return count($this->forDay($day)) > 0;
    }

    public function isClosedOn(string $day): bool
    {
        return !$this->isOpenOn($day);
    }

    public function isOpenAt(DateTimeInterface $dateTime): bool
    {
        $workingTime = WorkingTime::fromDateTime($dateTime);

        if ($this->forDate($dateTime)->isOpenAt($workingTime))
            return true;

        // Cover the edge case where we have late night opening,
        // but are closed the next day and the date range falls
        // inside the late night opening
        return $this->forDate(
            Carbon::parse($dateTime)->subDay()
        )->opensLateAt($workingTime);
    }

    public function isClosedAt(DateTimeInterface $dateTime): bool
    {
        return !$this->isOpenAt($dateTime);
    }

    public function nextOpenAt(DateTimeInterface $dateTime)
    {
        if (!$dateTime instanceof DateTimeImmutable)
            $dateTime = clone $dateTime;

        $nextOpenAt = $this->forDate($dateTime)->nextOpenAt(
            WorkingTime::fromDateTime($dateTime)
        );

        if (!$this->hasPeriod())
            return null;

        while ($nextOpenAt === false) {
            $dateTime = $dateTime->modify('+1 day')->setTime(0, 0);
            $workingTime = WorkingTime::fromDateTime($dateTime);

            $forDate = $this->forDate($dateTime);
            $nextOpenAt = !$forDate->isEmpty()
                ? $forDate->nextOpenAt($workingTime)
                : false;
        }

        return $dateTime->setTime(
            $nextOpenAt->toDateTime()->format('G'),
            $nextOpenAt->toDateTime()->format('i')
        );
    }

    /**
     * Returns the next closed time.
     *
     * @param \DateTimeInterface $dateTime
     * @return \DateTimeInterface
     */
    public function nextCloseAt(DateTimeInterface $dateTime)
    {
        if (!$dateTime instanceof DateTimeImmutable)
            $dateTime = clone $dateTime;

        $nextCloseAt = $this->forDate($dateTime)->nextCloseAt(
            WorkingTime::fromDateTime($dateTime)
        );

        if (!$this->hasPeriod())
            return null;

        while ($nextCloseAt === false) {
            $dateTime = $dateTime->modify('+1 day')->setTime(0, 0);
            $workingTime = WorkingTime::fromDateTime($dateTime);

            $forDate = $this->forDate($dateTime);
            $nextCloseAt = !$forDate->isEmpty()
                ? $forDate->nextCloseAt($workingTime)
                : false;
        }

        $dateTime = $dateTime->setTime(
            $nextCloseAt->toDateTime()->format('G'),
            $nextCloseAt->toDateTime()->format('i')
        );

        return $dateTime;
    }

    /**
     * @param DateTime|null $dateTime
     * @return WorkingPeriod
     */
    public function getPeriod($dateTime = null)
    {
        return $this->forDate($this->parseDate($dateTime));
    }

    public function getPeriods()
    {
        return $this->periods;
    }

    public function getOpenTime($format = null)
    {
        $time = $this->nextOpenAt(new DateTime());

        return ($time && $format) ? $time->format($format) : $time;
    }

    public function getCloseTime($format = null)
    {
        $time = $this->nextCloseAt(new DateTime());

        return ($time && $format) ? $time->format($format) : $time;
    }

    /**
     * @param DateTime|mixed Date or timestamp
     *
     * @return string
     */
    public function checkStatus($dateTime = null)
    {
        $dateTime = $this->parseDate($dateTime);

        if ($this->isOpenAt($dateTime))
            return WorkingPeriod::OPEN;

        if ($this->nextOpenAt($dateTime))
            return WorkingPeriod::OPENING;

        if ($this->isClosedAt($dateTime))
            return WorkingPeriod::CLOSED;

        return WorkingPeriod::CLOSED;
    }

    /**
     * @param int $interval
     * @param \DateTime|null $dateTime
     * @param int $leadTimeMinutes
     * @return Collection
     * @throws \Exception
     */
    public function getTimeslot(int $interval = 15, DateTime $dateTime = null, int $leadTimeMinutes = 25)
    {
        $dateTime = Carbon::instance($this->parseDate($dateTime));
        $interval = new DateInterval('PT'.($interval ?: 15).'M');
        $leadTime = new DateInterval('PT'.$leadTimeMinutes.'M');

        $timeslots = [];
        $datePeriod = $this->createPeriodForDays($dateTime);

        foreach ($datePeriod ?: [] as $date) {
            $dateString = Carbon::instance($date)->toDateString();

            $periodTimeslot = $this->forDate($date)
                ->timeslot($date, $interval, $leadTime)
                ->filter(function ($timeslot) use ($dateTime, $leadTimeMinutes) {
                    return $this->isTimeslotValid($timeslot, $dateTime, $leadTimeMinutes);
                })
                ->mapWithKeys(function ($timeslot) {
                    return [$timeslot->getTimestamp() => $timeslot];
                });

            if ($periodTimeslot->isEmpty())
                continue;

            $timeslots[$dateString] = $periodTimeslot->all();
        }

        return collect($timeslots);
    }

    public function generateTimeslot(DateTime $date, DateInterval $interval, ?DateInterval $leadTime = null)
    {
        if (is_null($leadTime))
            $leadTime = $interval;

        return $this->forDate($date)
            ->timeslot($date, $interval, $leadTime)
            ->filter(function ($timeslot) use ($date, $leadTime) {
                $dateTime = $date->copy()->setTimeFromTimeString($timeslot->format('H:i'));

                return $this->isTimeslotValid($timeslot, $dateTime, $leadTime->i);
            })
            ->mapWithKeys(function ($timeslot) {
                return [$timeslot->getTimestamp() => $timeslot];
            });
    }

    public function setPeriods(array $periods)
    {
        foreach ($periods as $day => $period) {
            $this->periods[$day] = WorkingPeriod::create($period);
        }
    }

    public function setExceptions(array $exceptions)
    {
        foreach ($exceptions as $day => $exception) {
            $this->exceptions[$day] = WorkingPeriod::create($exception);
        }
    }

    protected function parseDate($start = null)
    {
        if (!$start)
            return new DateTime();

        if (is_string($start))
            return new DateTime($start);

        if ($start instanceof DateTime)
            return $start;

        throw new InvalidArgumentException('The datetime must be an instance of DateTime.');
    }

    protected function parsePeriods($periods)
    {
        $parsedPeriods = [];
        foreach ($periods as $day => $period) {
            if ($period instanceof Contracts\WorkingHourInterface) {
                if (!$period->isEnabled()) continue;

                $day = WorkingDay::normalizeName($period->getDay());
                $parsedPeriods[$day][] = [
                    $period->getOpen(),
                    $period->getClose(),
                ];
            }
            elseif (is_array($period)) {
                $day = WorkingDay::normalizeName($day);
                $parsedPeriods[$day] = array_merge(
                    $parsedPeriods[$day] ?? [], $period
                );
            }
        }

        return $parsedPeriods;
    }

    protected function applyTimezone(DateTimeInterface $date)
    {
        if ($this->timezone && method_exists($date, 'setTimezone'))
            $date = $date->setTimezone($this->timezone);

        return $date;
    }

    protected function isTimeslotValid(DateTimeInterface $timeslot, DateTimeInterface $dateTime, int $leadTimeMinutes)
    {
        if (Carbon::instance($dateTime)->gt($timeslot) || Carbon::now()->gt($timeslot))
            return false;

        if (Carbon::now()->diffInMinutes($timeslot) < $leadTimeMinutes)
            return false;

        if (!$this->isBetweenPeriodForDays($timeslot))
            return false;

        // +2 as we subtracted a day and need to count the current day
        if (Carbon::instance($dateTime)->addDays($this->maxDays + 2)->lt($timeslot))
            return false;

        $result = Event::fire('igniter.workingSchedule.timeslotValid', [$this, $timeslot], true);

        return is_bool($result) ? $result : true;
    }

    protected function hasPeriod()
    {
        foreach ($this->periods as $period) {
            if (!$period->isEmpty())
                return true;
        }

        foreach ($this->exceptions as $exception) {
            if (!$exception->isEmpty())
                return true;
        }

        return false;
    }

    protected function createPeriodForDays($dateTime)
    {
        $startDate = $dateTime->copy()->startOfDay()->subDays(2);
        if (!$startDate = $this->nextOpenAt($startDate))
            return false;

        $endDate = $dateTime->copy()->endOfDay()->addDays($this->maxDays);
        if ($this->forDate($endDate)->closesLate())
            $endDate->addDay();

        $nextEndDate = $this->nextCloseAt($endDate->copy()->subDay());
        if ($nextEndDate->lt($dateTime))
            $endDate = $nextEndDate->addDay();

        return new DatePeriod($startDate, new DateInterval('P1D'), $endDate);
    }

    protected function isBetweenPeriodForDays($timeslot)
    {
        return Carbon::instance($timeslot)->between(
            now()->startOfDay()->addDays($this->minDays),
            now()->endOfDay()->addDays($this->maxDays + 2)
        );
    }
}
