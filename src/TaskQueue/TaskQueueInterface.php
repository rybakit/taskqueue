<?php

namespace Rybakit\TaskQueue;

use Rybakit\TaskQueue\Task\TaskInterface;

interface TaskQueueInterface
{
    /*
    function push($task);
    function pop($timeout = 10);
    function size();
    function peek($limit = 1, $offset = null);
    */

    /**
     * @param mixed $task
     * @throws \Exception
     */
    function push($task);

    /**
     * @param array $taskNames
     *
     * @return \Rybakit\TaskQueue\Task\TaskInterface|false
     */
    function pop(array $taskNames = array());

    /**
     * @param array $taskNames
     * @param int $limit
     * @param int $skip
     *
     * @return \Iterator
     */
    function peek(array $taskNames = array(), $limit = 1, $skip = 0);

    /**
     * @param mixed $task
     * @throws \Exception
     *
     * @return boolean
     */
    //function remove($task);

}