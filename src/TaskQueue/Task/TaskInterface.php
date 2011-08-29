<?php

namespace Rybakit\TaskQueue\Task;

interface TaskInterface
{
    /**
     * Returns the name of the task.
     *
     * @return string The task name
     */
    function getName();

    /**
     * Runs the task.
     *
     * @param \Closure|string|array $callback A PHP callback to run.
     *
     * @return mixed
     */
    function run($callable);

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