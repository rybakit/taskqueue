<?php

namespace TaskQueue\Task;

use TaskQueue\Queue\QueueInterface;

interface TaskInterface
{
    /**
     * TODO: remove it?
     *
     * @return mixed
     */
    function getPayload();

    /**
     * @return \DateTime|null
     */
    function getEta();

    /**
     * Runs the task.
     *
     * @param \Closure|string|array $callback A PHP callback to run.
     * @param \TaskQueue\Queue\QueueInterface $queue
     *
     * @return mixed
     */
    function run($callable, QueueInterface $queue);

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