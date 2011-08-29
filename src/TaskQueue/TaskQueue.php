<?php

namespace Rybakit\TaskQueue;

use Rybakit\TaskQueue\Task\TaskInterface;
use Rybakit\TaskQueue\Log\LoggerInterface;
use Rybakit\TaskQueue\Log\NullLogger;

class TaskQueue implements TaskQueueInterface
{
    /**
     * @var \Rybakit\TaskQueue\TaskQueueInterface
     */
    protected $backend;

    /**
     * @var \Rybakit\TaskQueue\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param \Rybakit\TaskQueue\TaskQueueInterface $backend
     * @param \Rybakit\TaskQueue\Log\LoggerInterface|null $logger
     */
    public function __construct(TaskQueueInterface $backend, LoggerInterface $logger = null)
    {
        $this->backend = $backend;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @return \Rybakit\TaskQueue\TaskQueueInterface
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
     * @see TaskQueueInterface::add()
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
     * @see TaskQueueInterface::next()
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