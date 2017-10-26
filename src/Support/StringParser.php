<?php

namespace Igniter\Flame\Support;

use RecursiveArrayIterator;

class StringParser
{
    protected $left;

    protected $right;

    /**
     * @param string $left The left delimiter
     * @param string $right The right delimiter
     */
    public function __construct($left = '{', $right = '}')
    {
        $this->left = $left;
        $this->right = $right;
    }

    /**
     * @param string $template The template string
     * @param string|array $value The value the template will be rendered with
     *
     * @return string The rendered template
     */
    public function parse($template, $value)
    {
        $result = $template;
        if (!is_array($value))
            $value = ['' => $value];
        foreach (new NestedKeyIterator(new RecursiveArrayIterator($value)) as $key => $value) {
            $result = str_replace($this->left.$key.$this->right, $value, $result);
        }

        return $result;
    }
}