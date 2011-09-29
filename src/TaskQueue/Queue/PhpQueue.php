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
        $this->innerQueue->insert($task, array($eta->getTimestamp(), $this->queueOrder--));
    }

    /**
     * @see QueueInterface::pop()
     */
    public function pop()
    {
        if ($this->innerQueue->isEmpty()) {
            return false;
        }

        return $this->innerQueue->extract();
    }

    /**
     * TODO: throw exeptions on invalid arguments?
     *
     * @see QueueInterface::peek()
     */
    public function peek($limit = 1, $skip = 0)
    {
        if ($this->innerQueue->isEmpty()) {
            return new \EmptyIterator();
        }

        $tasks = array();
        foreach (clone $this->innerQueue as $task) {
            if ($skip > 0) {
                $skip--;
                continue;
            }
            if ($limit > 0) {
                $tasks[] = $task;
                $limit--;
            }
        }

        return new \ArrayIterator($tasks);
    }
}