<?php

namespace Igniter\Flame\Location\Contracts;

interface OrderTypeInterface
{
    public function getOpenDescription(): string;

    public function getOpeningDescription(string $format): string;

    public function getClosedDescription(): string;

    public function getDisabledDescription(): string;

    public function isActive(): bool;

    public function isDisabled(): bool;
}
