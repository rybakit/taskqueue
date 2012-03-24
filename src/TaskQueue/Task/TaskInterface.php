<?php

namespace TaskQueue\Task;

use TaskQueue\Queue\QueueInterface;

interface TaskInterface
{
    /**
     * @return mixed
     */
    function getPayload();

    /**
     * @return \DateTime|null
     */
    function getEta();

    /**
     * Reschedules the task in the future (when an unexpected error occurred during execution).
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