<?php

namespace TaskQueue\Task;

interface TaskAwareInterface
{
    function setTaskQueue(TaskInterface $task);
}