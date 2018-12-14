<?php namespace Igniter\Flame\Location\Contracts;

interface WorkingHourInterface
{
    public function getDay();

    public function getOpen();

    public function getClose();

    public function isEnabled();
}