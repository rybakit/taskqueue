<?php

namespace TaskQueue\Task;

use TaskQueue\TaskQueueInterface;

interface TaskInterface
{
    /**
     * Runs the task.
     *
     * @param \Closure|string|array $callback A PHP callback to run.
     * @param \TaskQueue\TaskQueueInterface $queue
     *
     * @return mixed
     */
    function run($callable, TaskQueueInterface $queue);

    /**
     * Reschedules the task in the future (when a task fails).
     *
     * @return boolean True if task rescheduled, false otherwise
     */
    function reschedule();

    /**
     * Returns a string representation of the task.
     *
     * @return string The string representation
     */
    function __toString();
}