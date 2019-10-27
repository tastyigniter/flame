<?php namespace Igniter\Flame\Support;

class StrHelper extends \Illuminate\Support\Str
{
    /**
     * Converts number to its ordinal English form.
     *
     * This method converts 13 to 13th, 2 to 2nd ...
     *
     * @param integer $number Number to get its ordinal value
     * @return string Ordinal representation of given string.
     */
    public static function ordinal($number)
    {
        if (in_array($number % 100, range(11, 13))) {
            return $number.'th';
        }

        switch ($number % 10) {
            case 1:
                return $number.'st';
            case 2:
                return $number.'nd';
            case 3:
                return $number.'rd';
            default:
                return $number.'th';
        }
    }

    /**
     * Converts line breaks to a standard \r\n pattern.
     * @param string|string[] $string
     * @return string|string[]|null
     */
    public static function normalizeEol($string)
    {
        return preg_replace('~\R~u', "\r\n", $string);
    }

    /**
     * Removes the starting slash from a class namespace \
     * @param mixed $name
     * @return mixed|string
     */
    public static function normalizeClassName($name)
    {
        if (is_object($name))
            $name = get_class($name);

        $name = '\\'.ltrim($name, '\\');

        return $name;
    }

    /**
     * Generates a class ID from either an object or a string of the class name.
     * @param $name
     * @return string
     */
    public static function getClassId($name)
    {
        if (is_object($name))
            $name = get_class($name);

        $name = ltrim($name, '\\');
        $name = str_replace('\\', '_', $name);

        return strtolower($name);
    }

    /**
     * Returns a class namespace
     * @param $name
     * @return bool|string
     */
    public static function getClassNamespace($name)
    {
        $name = static::normalizeClassName($name);

        return substr($name, 0, strrpos($name, "\\"));
    }

    /**
     * If $string begins with any number of consecutive symbols,
     * returns the number, otherwise returns 0
     *
     * @param string $string
     * @param string $symbol
     * @return int
     */
    public static function getPrecedingSymbols($string, $symbol)
    {
        return strlen($string) - strlen(ltrim($string, $symbol));
    }
}