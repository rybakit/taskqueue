<?php

namespace TaskQueue;

use TaskQueue\Task\TaskInterface;

interface TaskQueueInterface
{
    /**
     * @param mixed $task
     * @throws \Exception
     */
    function push($task);

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
     * @param mixed $task
     * @throws \Exception
     *
     * @return boolean
     */
    //function remove($task);

    //function size();
    //function get($unique);
}