<?php

namespace Igniter\Flame\Currency\Contracts;

interface CurrencyInterface
{
    public function getId();

    public function getName();

    public function getCode();

    public function getSymbol();

    public function getSymbolPosition();

    public function getFormat();

    public function getRate();

    public function isEnabled();

    public function updateRate($rate);
}