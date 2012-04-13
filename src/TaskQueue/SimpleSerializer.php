<?php

namespace TaskQueue;

use TaskQueue\Task\TaskInterface;

class SimpleSerializer
{
    public function serialize(TaskInterface $task)
    {
        return base64_encode(serialize($task));
    }

    public function unserialize($serialized)
    {
        return unserialize(base64_decode($serialized));
    }
}