<?php

namespace Igniter\Flame\ActivityLog\Contracts;

use Igniter\Flame\ActivityLog\Models\Activity;

interface ActivityInterface
{
    /**
     * Get the user that triggered the activity.
     *
     * @return mixed
     */
    public function getCauser();

    /**
     * Get the model that is the subject of this activity.
     *
     * @return mixed
     */
    public function getSubject();

    /**
     * Get the data to be stored with the activity.
     *
     * @return array|null
     */
    public function getProperties();

    /**
     * Get the type of this activity.
     *
     * @return string
     */
    public static function getType();

    public static function getUrl(Activity $activity);

    public static function getMessage(Activity $activity);

    /**
     * Get the name of the model class for the subject of this activity.
     *
     * @return string
     */
    public static function getSubjectModel();
}