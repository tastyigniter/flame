<?php

namespace Igniter\Flame\Translation\Contracts;

interface Driver
{
    /**
     * @param $locale
     * @param $group
     * @param null $namespace
     *
     * @return mixed
     */
    public function load($locale, $group, $namespace = null);
}