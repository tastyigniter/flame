<?php namespace Igniter\Flame\Location;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Igniter\Flame\Location\Exceptions\WorkingHourException;

class WorkingTime
{
    /** @var int */
    protected $hours;

    /** @var int */
    protected $minutes;

    protected function __construct(int $hours, int $minutes)
    {
        $this->hours = $hours;
        $this->minutes = $minutes;
    }

    public static function create(string $string): self
    {
        if (!preg_match('/^([0-1]\d)|(2[0-4]):[0-5]\d$/', $string))
            throw new WorkingHourException("The string `{$string}` isn't a valid time string. A time string must be a formatted as `18:00`.");

        list($hours, $minutes) = explode(':', $string);

        return new self($hours, $minutes);
    }

    public static function fromDateTime(DateTimeInterface $dateTime): self
    {
        return self::create($dateTime->format('H:i'));
    }

    public function hours(): int
    {
        return $this->hours;
    }

    public function minutes(): int
    {
        return $this->minutes;
    }

    public function isSame(self $time): bool
    {
        return (string)$this === (string)$time;
    }

    public function isAfter(self $time): bool
    {
        if ($this->isSame($time)) {
            return FALSE;
        }

        if ($this->hours > $time->hours) {
            return TRUE;
        }

        return $this->hours === $time->hours && $this->minutes >= $time->minutes;
    }

    public function isBefore(self $time): bool
    {
        if ($this->isSame($time)) {
            return FALSE;
        }

        return !$this->isAfter($time);
    }

    public function isSameOrAfter(self $time): bool
    {
        return $this->isSame($time) || $this->isAfter($time);
    }

    public function diff(self $time): \DateInterval
    {
        return $this->toDateTime()->diff($time->toDateTime());
    }

    /**
     * Convert to DateTime object.
     *
     * @param \DateTime|null $date
     * @return \DateTime
     */
    public function toDateTime(DateTime $date = null): DateTime
    {
        if (!$date) {
            $date = new DateTime('1970-01-01 00:00:00');
        }
        elseif (!($date instanceof DateTimeImmutable)) {
            $date = clone $date;
        }

        return $date->setTime($this->hours, $this->minutes);
    }

    public function format(string $format = 'H:i'): string
    {
        return $this->toDateTime()->format($format);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
