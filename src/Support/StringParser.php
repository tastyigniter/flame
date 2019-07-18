<?php

namespace Igniter\Flame\Support;

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
     * @param string|array $data The value the template will be rendered with
     *
     * @return string The rendered template
     */
    public function parse($template, $data)
    {
        if (!is_array($data))
            $data = ['' => $data];

        $replace = [];
        foreach ($data as $key => $value) {
            $replace = array_merge(
                $replace,
                is_array($value)
                    ? $this->parsePair($key, $value, $template)
                    : $this->parseSingle($key, $value, $template)
            );
        }

        $result = strtr($template, $replace);

        return $result;
    }

    protected function parseSingle($key, $value, $template)
    {
        if (!is_scalar($value))
            $value = '';

        return [$this->left.$key.$this->right => $value];
    }

    protected function parsePair($key, $data, $template)
    {
        $replace = [];
        preg_match_all(
            '#'.preg_quote($this->left.$key.$this->right, '/').'(.+?)'.preg_quote($this->left.'/'.$key.$this->right, '/').'#s',
            $template,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $str = '';
            foreach ($data as $row) {
                $temp = [];
                foreach ($row as $key => $val) {
                    if (is_array($val)) {
                        $pair = $this->parsePair($val, $key, $match[1]);
                        if (!empty($pair)) {
                            $temp = array_merge($temp, $pair);
                        }
                        continue;
                    }
                    $temp[$this->left.$key.$this->right] = $val;
                }
                $str .= strtr($match[1], $temp);
            }
            $replace[$match[0]] = $str;
        }

        return $replace;
    }
}