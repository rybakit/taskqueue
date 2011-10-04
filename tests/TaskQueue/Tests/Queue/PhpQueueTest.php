<?php

namespace TaskQueue\Tests\Queue;

use TaskQueue\Queue\PhpQueue;
use TaskQueue\Task\Task;

class PhpQueueTest extends AbstractQueueTest
{
    public function createQueue()
    {
        return new PhpQueue();
    }
}