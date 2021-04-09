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

    protected $days;

    /**
     * @var DateTime
     */
    protected $now;

    /**
     * @param null $timezone
     * @param int $days
     */
    public function __construct($timezone = null, $days = 5)
    {
        $this->timezone = $timezone ? new DateTimeZone($timezone) : null;
        $this->days = (int)$days;

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
        $this->now = $now;

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

    public function days()
    {
        return $this->days;
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
        return $this->nextOpenAt(new DateTime()) ? TRUE : FALSE;
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
            return TRUE;

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

        while ($nextOpenAt === FALSE) {
            $dateTime = $dateTime->modify('+1 day')->setTime(0, 0);
            $workingTime = WorkingTime::fromDateTime($dateTime);

            $forDate = $this->forDate($dateTime);
            $nextOpenAt = !$forDate->isEmpty()
                ? $forDate->nextOpenAt($workingTime)
                : FALSE;
        }

        $dateTime = $dateTime->setTime(
            $nextOpenAt->toDateTime()->format('G'),
            $nextOpenAt->toDateTime()->format('i')
        );

        return $dateTime;
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

        while ($nextCloseAt === FALSE) {
            $dateTime = $dateTime->modify('+1 day')->setTime(0, 0);
            $workingTime = WorkingTime::fromDateTime($dateTime);

            $forDate = $this->forDate($dateTime);
            $nextCloseAt = !$forDate->isEmpty()
                ? $forDate->nextCloseAt($workingTime)
                : FALSE;
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

        return ($time AND $format) ? $time->format($format) : $time;
    }

    public function getCloseTime($format = null)
    {
        $time = $this->nextCloseAt(new DateTime());

        return ($time AND $format) ? $time->format($format) : $time;
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

        $timeslots = [];
        $datePeriod = $this->createPeriodForDays($dateTime);
        foreach ($datePeriod as $date) {
            $dateString = $date->toDateString();

            $periodTimeslot = $this->forDate($date)
                ->timeslot($date, $interval)
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
            ->mapWithKeys(function ($timeslot) {
                return [$timeslot->getTimestamp() => $timeslot];
            });
    }

    protected function now()
    {
        return $this->now ?? $this->now = new DateTime();
    }

    protected function setPeriods(array $periods)
    {
        foreach ($periods as $day => $period) {
            $this->periods[$day] = WorkingPeriod::create($period);
        }
    }

    protected function setExceptions(array $exceptions)
    {
        foreach ($exceptions as $day => $exception) {
            $this->exceptions[$day] = WorkingPeriod::create($exception);
        }
    }

    protected function parseDate($start = null)
    {
        if (!$start)
            return $this->now();

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
        if ($this->timezone AND method_exists($date, 'setTimezone'))
            $date = $date->setTimezone($this->timezone);

        return $date;
    }

    protected function isTimeslotValid(DateTimeInterface $date, DateTimeInterface $dateTime, int $leadTimeMinutes)
    {
        if (Carbon::instance($dateTime)->gt($date) || Carbon::now()->gt($date))
            return FALSE;

        if (Carbon::now()->diffInMinutes($date) < $leadTimeMinutes)
            return FALSE;

        if (Carbon::instance($dateTime)->addDays($this->days + 2)->lt($date)) // +2 as we subtracted a day and need to count the current day
            return FALSE;

        $result = Event::fire('igniter.workingSchedule.timeslotValid', [$this, $date], TRUE);

        return is_bool($result) ? $result : TRUE;
    }

    protected function hasPeriod()
    {
        foreach ($this->periods as $period) {
            if (!$period->isEmpty())
                return TRUE;
        }

        if (!empty($this->exceptions))
            return TRUE;

        return FALSE;
    }

    protected function createPeriodForDays($dateTime)
    {
        $startDate = $dateTime->copy()->startOfDay()->subDays(2);
        $endDate = $dateTime->copy()->endOfDay()->addDays($this->days + 1);

        if ($this->forDate($endDate)->closesLate())
            $endDate->addDay();

        $nextEndDate = $this->nextCloseAt($endDate->copy()->subDay());
        if ($nextEndDate->lt($dateTime))
            $endDate = $nextEndDate->addDay();

        return new DatePeriod($startDate, new DateInterval('P1D'), $endDate);
    }
}
