<?php

namespace TaskQueue\Worker;

use TaskQueue\TaskQueueInterface;
use TaskQueue\Task\TaskInterface;

class CallbackWorker extends Worker
{
    /**
     * @var \Closure|string|array
     */
    protected $callback;

    /**
     * Sets a callback.
     *
     * @param \Closure|string|array $callback A PHP callback to run.
     *
     * @throws \InvalidArgumentException
     */
    public function setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback specified.');
        }

        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function work()
    {
        if (!$this->callback) {
            throw new \LogicException('No callback specified.');
        }

        parent::work();
    }

    /**
     * {@inheritdoc}
     */
    protected function runTask(TaskInterface $task, TaskQueueInterface $queue)
    {
        $task->run($this->callback, $queue);
    }
}