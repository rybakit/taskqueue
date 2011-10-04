<?php

namespace TaskQueue\Queue;

use TaskQueue\Task\TaskInterface;

interface QueueInterface extends \Countable
{
    /**
     * @param \TaskQueue\Task\TaskInterface $task
     */
    function push(TaskInterface $task);

    /**
     * @return \TaskQueue\Task\TaskInterface|false
     */
    function pop();

    /**
     * TODO: rename this method
     *
     * @param int $limit
     * @param int $skip
     *
     * @throws \OutOfRangeException
     *
     * @return \Iterator
     */
    function peek($limit = 1, $skip = 0);

    /**
     * Removes all tasks from the queue.
     */
    function clear();
}