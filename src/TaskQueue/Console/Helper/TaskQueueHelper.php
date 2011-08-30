<?php

namespace TaskQueue\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use TaskQueue\TaskQueueInterface;

class TaskQueueHelper extends Helper
{
    protected $taskQueue;

    public function __construct(TaskQueueInterface $taskQueue)
    {
        $this->taskQueue = $taskQueue;
    }

    public function getTaskQueue()
    {
        return $this->taskQueue;
    }

    public function getName()
    {
        return 'taskQueue';
    }
}