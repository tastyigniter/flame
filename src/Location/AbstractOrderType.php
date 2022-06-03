<?php

namespace Igniter\Flame\Location;

use Igniter\Flame\Location\Contracts\OrderTypeInterface;

abstract class AbstractOrderType implements OrderTypeInterface
{
    public const ASAP_ONLY = 1;

    public const LATER_ONLY = 2;

    /**
     * @var \Igniter\Flame\Location\Models\AbstractLocation
     */
    protected $model;

    protected $config;

    protected $code;

    protected $name;

    /**
     * @var \Igniter\Flame\Location\WorkingSchedule
     */
    protected $schedule;

    public function __construct($model, $config)
    {
        $this->model = $model;
        $this->config = $config;
        $this->code = $config['code'];
        $this->name = $config['name'];
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return lang($this->name);
    }

    public function getInterval(): int
    {
        return $this->model->getOrderTimeInterval($this->code);
    }

    public function getLeadTime(): int
    {
        return $this->model->getOrderLeadTime($this->code);
    }

    public function getFutureDays(): int
    {
        return $this->model->hasFutureOrder($this->code)
            ? $this->model->futureOrderDays($this->code)
            : 0;
    }

    public function getMinimumFutureDays(): int
    {
        return $this->model->hasFutureOrder($this->code)
            ? $this->model->minimumFutureOrderDays($this->code)
            : 0;
    }

    public function getMinimumOrderTotal()
    {
        return $this->model->getMinimumOrderTotal($this->code);
    }

    public function getSchedule(): WorkingSchedule
    {
        if (!is_null($this->schedule))
            return $this->schedule;

        $schedule = $this->model->newWorkingSchedule(
            $this->code, [$this->getMinimumFutureDays(), $this->getFutureDays()]
        );

        return $this->schedule = $schedule;
    }

    public function getScheduleRestriction(): int
    {
        if ($this->model->getOption('limit_orders'))
            return static::LATER_ONLY;

        if ($this->model->hasFutureOrder($this->code))
            return 0;

        return $this->model->getOrderTimeRestriction($this->code);
    }
}
