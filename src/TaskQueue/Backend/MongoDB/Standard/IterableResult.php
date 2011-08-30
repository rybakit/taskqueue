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
     * A PHP callback to transform result set into another structure (e.g. object).
     *
     * @var \Closure|string|array
     */
    protected $hydrator;

    /**
     * Constructor.
     *
     * @param \MongoCursor $mongoCursor
     * @param \Closure|string|array $hydrator
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\MongoCursor $mongoCursor, $hydrator)
    {
        if (!is_callable($hydrator)) {
            throw new \InvalidArgumentException('The given hydrator is not a valid callable.');
        }

        $this->mongoCursor = $mongoCursor;
        $this->hydrator = $hydrator;
    }

    public function current()
    {
        $data = $this->mongoCursor->current();

        return is_array($data) ? call_user_func($this->hydrator, $data) : false;
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
