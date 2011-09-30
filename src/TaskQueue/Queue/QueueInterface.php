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
     * @param int $limit
     * @param int $skip
     *
     * @return \Iterator
     */
    function peek($limit = 1, $skip = 0);

    /**
     * Removes all tasks from the queue.
     */
    function clear();
}