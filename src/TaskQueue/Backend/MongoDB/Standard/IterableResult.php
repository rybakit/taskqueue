<?php

namespace Rybakit\TaskQueue\Backend\MongoDB\Standard;

class IterableResult implements \Iterator
{
    /**
     * The PHP MongoCursor instance.
     *
     * @var \MongoCursor
     */
    protected $mongoCursor;

    /**
     * A PHP callback to convert data to object.
     *
     * @var \Closure|string|array
     */
    protected $converter;

    /**
     * Constructor.
     *
     * @param \MongoCursor $mongoCursor
     * @param \Closure|string|array $converter
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\MongoCursor $mongoCursor, $converter)
    {
        if (!is_callable($converter)) {
            throw new \InvalidArgumentException('The given converter callback is not a valid callable.');
        }

        $this->mongoCursor = $mongoCursor;
        $this->converter = $converter;
    }

    public function current()
    {
        return call_user_func($this->converter, $this->mongoCursor->current());
    }

    public function key()
    {
        return $this->mongoCursor->key();
    }

    public function rewind()
    {
        return $this->mongoCursor->rewind();
    }

    public function next()
    {
        return $this->mongoCursor->next();
    }

    public function valid()
    {
        return $this->mongoCursor->valid();
    }
}
