<?php

namespace TaskQueue\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use TaskQueue\Queue\QueueInterface;

class TaskQueueHelper extends Helper
{
    protected $queue;

    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function getName()
    {
        return 'taskQueue';
    }
}