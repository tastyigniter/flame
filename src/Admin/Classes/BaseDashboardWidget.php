<?php

namespace Igniter\Admin\Classes;

use Igniter\System\Traits\PropertyContainer;

/**
 * Dashboard Widget base class
 * Dashboard widgets are used inside the DashboardContainer.
 */
class BaseDashboardWidget extends BaseWidget
{
    use PropertyContainer;

    public function __construct($controller, $properties = [])
    {
        $this->properties = $this->validateProperties($properties);

        parent::__construct($controller);
    }
}
