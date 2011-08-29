<?php

namespace Rybakit\TaskQueue\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use Rybakit\TaskQueue\TaskQueueInterface;

class JobQueueHelper extends Helper
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