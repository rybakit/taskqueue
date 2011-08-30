<?php

namespace TaskQueue;

use TaskQueue\Task\TaskInterface;
use TaskQueue\Log\LoggerInterface;
use TaskQueue\Log\NullLogger;

class TaskQueue implements TaskQueueInterface
{
    /**
     * @var \TaskQueue\TaskQueueInterface
     */
    protected $backend;

    /**
     * @var \TaskQueue\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param \TaskQueue\TaskQueueInterface $backend
     * @param \TaskQueue\Log\LoggerInterface|null $logger
     */
    public function __construct(TaskQueueInterface $backend, LoggerInterface $logger = null)
    {
        $this->backend = $backend;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @return \TaskQueue\TaskQueueInterface
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * @return Log\LoggerInterface|Log\NullLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @see TaskQueueInterface::push()
     */
    public function push($task)
    {
        try {
            $this->backend->push($task);
        } catch (\Exception $e) {
            $this->logger->err(sprintf('Unable to push task %s: %s.', $task, $e->getMessage()));
            throw $e;
        }

        $this->logger->debug(sprintf('Task %s was successfully pushed.', $task));
    }

    /**
     * @see TaskQueueInterface::pop()
     */
    public function pop(array $taskNames = array())
    {
        try {
            $task = $this->backend->pop($taskNames);
        } catch (\Exception $e) {
            $this->logger->err(sprintf('Unable to pop task: %s.', $e->getMessage()));
            throw $e;
        }

        return $task;
    }

    /**
     * @see TaskQueueInterface::peek()
     */
    public function peek(array $taskNames = array(), $limit = 1, $skip = 0)
    {
        try {
            $tasks = $this->backend->peek($taskNames, $limit, $skip);
        } catch (\Exception $e) {
            $this->logger->err(sprintf('Unable to peek task(s): %s.', $e->getMessage()));
            throw $e;
        }

        return $tasks;
    }
}