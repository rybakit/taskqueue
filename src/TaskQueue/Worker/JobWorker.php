<?php

namespace Rybakit\TaskQueue\Worker;

use Rybakit\TaskQueue\TaskQueueAwareInterface;
use Rybakit\TaskQueue\Job\JobInterface;
use Rybakit\TaskQueue\Task\Task;

class JobWorker extends Worker
{
    protected function runTask(Task $task)
    {
        $jobClass = $task->getName();

        if (!class_exists($jobClass)) {
            throw new \UnexpectedValueException(sprintf('Class "%s" does not exist.', $jobClass));
        }

        $r = new \ReflectionClass($jobClass);
        if (!$r->implementsInterface('JobInterface')) {
            throw new \UnexpectedValueException(sprintf('Class "%s" must implement JobInterface.', $jobClass));
        }

        $job = $r->hasMethod('__construct') ? $r->newInstance() : $r->newInstanceArgs($task->getPayload());

        // TODO remove direct link to currentQueue
        if ($job instanceof TaskQueueAwareInterface) {
            $job->setTaskQueue($this->currentQueue);
        }

        $task->run(array($job, 'execute'));
    }
}