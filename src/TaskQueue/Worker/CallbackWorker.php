<?php

namespace Rybakit\TaskQueue\Worker;

use Rybakit\TaskQueue\Exception\InvalidArgumentException;
use Rybakit\TaskQueue\Exception\MissingCallbackException;
use Rybakit\TaskQueue\Task\TaskInterface;

class CallbackWorker extends Worker
{
    /**
     * @var array
     */
    protected $callbacks = array();

    /**
     * Registers a callback.
     *
     * @param \Closure|string|array $callback A PHP callback to run.
     * @param string|null $taskName
     *
     * @throws \Rybakit\TaskQueue\Exception\InvalidArgumentException
     */
    public function registerCallback($callback, $taskName = null)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Invalid callback specified.');
        }

        $this->callbacks[(string) $taskName] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function filterTaskNames()
    {
        return array_key_exists('', $this->callbacks) ? array() : array_keys($this->callbacks);
    }

    /**
     * {@inheritdoc}
     */
    protected function runTask(TaskInterface $task)
    {
        $taskName = $task->getName();

        if (isset($this->callbacks[$taskName])) {
            $callback = $this->callbacks[$taskName];
        } else if (array_key_exists('', $this->callbacks)) {
            $callback = $this->callbacks[''];
        } else {
            throw new MissingCallbackException(sprintf('No callback is registered for "%" tasks.', $taskName));
        }

        $task->run($callback);
    }
}