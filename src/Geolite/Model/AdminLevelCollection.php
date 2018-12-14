<?php

namespace Igniter\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Exception\GeoliteException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AdminLevelCollection extends Collection
{
    const MAX_LEVEL_DEPTH = 5;

    /**
     * The items contained in the collection.
     *
     * @var \Igniter\Flame\Geolite\Model\AdminLevel[]
     */
    protected $items = [];

    /**
     * @param \Igniter\Flame\Geolite\Model\AdminLevel[] $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $this->validateAdminLevels($items);
    }

    /**
     * @param int $level
     *
     * @throws \OutOfBoundsException
     */
    protected function checkLevel(int $level)
    {
        if ($level <= 0 || $level > self::MAX_LEVEL_DEPTH) {
            throw new GeoliteException(sprintf(
                'Administrative level should be an integer in [1,%d], %d given',
                self::MAX_LEVEL_DEPTH, $level
            ));
        }
    }

    protected function validateAdminLevels(array $items)
    {
        $levels = [];
        foreach ($items as $adminLevel) {
            $level = $adminLevel->getLevel();
            $this->checkLevel($level);

            if ($this->has($level)) {
                throw new InvalidArgumentException(sprintf(
                    'Administrative level %d is defined twice', $level
                ));
            }

            $levels[$level] = $adminLevel;
        }

        ksort($levels, SORT_NUMERIC);

        return $levels;
    }
}