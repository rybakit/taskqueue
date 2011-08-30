<?php

namespace TaskQueue\Task;

interface TaskAwareInterface
{
    function setTask(TaskInterface $task);
}