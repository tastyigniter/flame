<?php namespace Igniter\Flame\Support;

/**
 * Class NestedKeyIterator
 * This iterator iterates recursively through an array,
 * returning as the current key an imploded list of the stacked keys
 * separated by a given separator.
 * Example:
 * <code>
 * $ary = [
 *      'foo' => 'bar',
 *      'baz' => ['foo' => ['uh' => 'ah'], 'oh' => 'eh']
 * ]
 * foreach (new NestedKeyIterator($ary) as $key => $value) {
 *  echo "$key: value\n",
 * }
 * </code>
 * prints
 *  foo: bar
 *  baz.foo.uh: ah
 *  baz.oh: eh
 * @package StringTemplate
 */
class NestedKeyIterator extends \RecursiveIteratorIterator
{
    private $stack = [];

    private $keySeparator;

    /**
     * @param \Traversable $iterator
     * @param string $separator
     * @param int $mode
     * @param int $flags
     */
    public function __construct(\Traversable $iterator, $separator = '.', $mode = \RecursiveIteratorIterator::LEAVES_ONLY, $flags = 0)
    {
        $this->keySeparator = $separator;
        parent::__construct($iterator, $mode, $flags);
    }

    /**
     * {@inheritdoc}
     */
    public function callGetChildren()
    {
        $this->stack[] = parent::key();

        return parent::callGetChildren();
    }

    /**
     * {@inheritdoc}
     */
    public function endChildren()
    {
        parent::endChildren();
        array_pop($this->stack);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        $keys = $this->stack;
        $keys[] = parent::key();

        return implode($this->keySeparator, $keys);
    }
}