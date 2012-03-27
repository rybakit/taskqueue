<?php

namespace TaskQueue\Tests\Queue;

use TaskQueue\Queue\InMemoryQueue;

class InMemoryQueueTest extends AbstractQueueTest
{
    protected function createQueue()
    {
        return new InMemoryQueue();
    }
}