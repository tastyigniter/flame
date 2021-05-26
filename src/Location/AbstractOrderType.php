<?php

namespace Igniter\Flame\Location;

use Igniter\Flame\Location\Contracts\OrderTypeInterface;

abstract class AbstractOrderType implements OrderTypeInterface
{
    /**
     * @var \Igniter\Flame\Location\Models\AbstractLocation
     */
    protected $model;

    protected $code;

    protected $name;

    /**
     * @var \Igniter\Flame\Location\WorkingSchedule
     */
    protected $schedule;

    public function __construct($model, $config)
    {
        $this->model = $model;
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

    public function getSchedule(): WorkingSchedule
    {
        if (!is_null($this->schedule))
            return $this->schedule;

        $schedule = $this->model->newWorkingSchedule(
            $this->code, $this->getFutureDays()
        );

        return $this->schedule = $schedule;
    }
}
