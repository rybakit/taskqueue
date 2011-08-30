<?php

namespace TaskQueue;

interface TaskQueueAwareInterface
{
    function setTaskQueue(TaskQueueInterface $taskQueue);
}