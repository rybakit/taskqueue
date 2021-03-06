<?php

namespace TaskQueue\Task;

interface TaskInterface
{
    /**
     * @return mixed
     */
    function getPayload();

    /**
     * Returns the earliest time when task will execute.
     *
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