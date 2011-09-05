<?php

namespace TaskQueue;

use TaskQueue\Task\TaskInterface;

interface TaskQueueInterface
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

    //function size();
}