<?php

namespace Igniter\Flame\Database\Traits;

/**
 * Mailable model trait
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   use \Igniter\Flame\Database\Traits\Mailable;
 *
 * To send mail:
 *
 *   $model->sendMailable('template_code');
 */
trait Mailable
{
    /**
     * Boot the sortable trait for this model.
     *
     * @return void
     */
    public static function bootMailable()
    {
    }

    public function sendMail($code)
    {
//        $sortableField = static::getSortOrderColumn();
//        return $query->orderBy($sortableField);

        $this->resetMailable();
    }

    public function mailableSendTo($code)
    {
        return [
            // list of emails
        ];
    }

    public function resetMailable()
    {
        $this->to = null;
    }

    public function setMailableTo($to)
    {
//        $sortableField = static::getSortOrderColumn();
//        return $query->orderBy($sortableField);
        return $this;
    }

    public function getMailableAttributes()
    {
        return $this->getAttributes();
    }

    public function getMailer()
    {
    }
}