<?php namespace Igniter\Flame\Pagic\Exception;

use RuntimeException;

class FileExistsException extends RuntimeException
{
    /**
     * Name of the affected directory path.
     *
     * @var string
     */
    protected $invalidPath;

    /**
     * Set the affected directory path.
     *
     * @param $path
     * @return $this
     */
    public function setInvalidPath($path)
    {
        $this->invalidPath = $path;

        $this->message = "A file already exists at [{$path}].";

        return $this;
    }

    /**
     * Get the affected directory path.
     *
     * @return string
     */
    public function getInvalidPath()
    {
        return $this->invalidPath;
    }
}
