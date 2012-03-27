<?php

namespace TaskQueue\Queue;

use TaskQueue\Task\TaskInterface;

interface QueueInterface
{
    /**
     * @param \TaskQueue\Task\TaskInterface $task
     */
    function push(TaskInterface $task);

    /**
     * @return \TaskQueue\Task\TaskInterface|bool false if queue is empty, a task instance otherwise
     */
    function pop();
}