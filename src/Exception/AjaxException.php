<?php

namespace Igniter\Flame\Exception;

class AjaxException extends BaseException
{

    /**
     * @var array Collection response contents.
     */
    protected $contents;

    /**
     * Constructor.
     * @param $contents
     */
    public function __construct($contents)
    {
        if (is_string($contents)) {
            $contents = ['result' => $contents];
        }

        $this->contents = $contents;

        parent::__construct(json_encode($contents));
    }

    /**
     * Returns invalid fields.
     */
    public function getContents()
    {
        return $this->contents;
    }
}
