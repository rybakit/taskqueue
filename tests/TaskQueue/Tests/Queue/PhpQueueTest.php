<?php

namespace TaskQueue\Tests\Queue;

use TaskQueue\Queue\PhpQueue;

class PhpQueueTest extends AbstractQueueTest
{
    protected function createQueue()
    {
        return new PhpQueue();
    }
}