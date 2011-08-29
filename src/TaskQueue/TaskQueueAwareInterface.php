<?php

namespace Rybakit\TaskQueue;

interface TaskQueueAwareInterface
{
    function setTaskQueue(TaskQueueInterface $taskQueue);
}