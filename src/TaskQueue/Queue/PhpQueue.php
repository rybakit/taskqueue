<?php

namespace TaskQueue\Queue;

use TaskQueue\Queue\QueueInterface;
use TaskQueue\Task\TaskInterface;

class PhpQueue implements QueueInterface
{
    /**
     * @var \SplPriorityQueue
     */
    protected $innerQueue;

    /**
     * @var int
     */
    protected $queueOrder = PHP_INT_MAX;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->innerQueue = new \SplPriorityQueue();
    }

    /**
     * @see QueueInterface::push()
     */
    public function push(TaskInterface $task)
    {
        $eta = $task->getEta() ?: new \DateTime();
        $this->innerQueue->insert($task, array(-$eta->getTimestamp(), $this->queueOrder--));
    }

    /**
     * @see QueueInterface::pop()
     */
    public function pop()
    {
        return $this->innerQueue->isEmpty() ? false : $this->innerQueue->extract();
    }

    /**
     * @see QueueInterface::peek()
     */
    public function peek($limit = 1, $skip = 0)
    {
        if ($limit <= 0) {
            // Parameter limit must either be -1 or a value greater than or equal 0
            throw new \OutOfRangeException('Parameter limit must be greater than 0.');
        }
        if ($skip < 0) {
            throw new \OutOfRangeException('Parameter skip must be greater than or equal 0.');
        }

        return new \LimitIterator(clone $this->innerQueue, $skip, $limit);
    }

    /**
     * @see QueueInterface::count()
     */
    public function count()
    {
        return $this->innerQueue->count();
    }

    /**
     * @see QueueInterface::clear()
     */
    public function clear()
    {
        $this->innerQueue = new \SplPriorityQueue();
        $this->queueOrder = PHP_INT_MAX;
    }
}