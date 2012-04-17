<?php

namespace TaskQueue\Log;

use TaskQueue\Queue\AdvancedQueueInterface;
use TaskQueue\Task\TaskInterface;

class LoggableQueue implements AdvancedQueueInterface
{
    /**
     * @var \TaskQueue\Queue\QueueInterface
     */
    protected $queue;

    /**
     * @var \TaskQueue\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param \TaskQueue\Queue\AdvancedQueueInterface $queue
     * @param \TaskQueue\Log\LoggerInterface $logger
     */
    public function __construct(AdvancedQueueInterface $queue, LoggerInterface $logger)
    {
        $this->queue = $queue;
        $this->logger = $logger;
    }

    /**
     * @return \TaskQueue\Queue\QueueInterface
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @return \TaskQueue\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @see QueueInterface::push()
     */
    public function push(TaskInterface $task)
    {
        try {
            $this->queue->push($task);
        } catch (\Exception $e) {
            $this->logger->err(sprintf('Unable to push task %s: %s.', $task, $e->getMessage()));
            throw $e;
        }

        $this->logger->debug(sprintf('Task %s was successfully pushed.', $task));
    }

    /**
     * @see QueueInterface::pop()
     */
    public function pop()
    {
        try {
            $task = $this->queue->pop();
        } catch (\Exception $e) {
            $this->logger->err(sprintf('Unable to pop task: %s.', $e->getMessage()));
            throw $e;
        }

        return $task;
    }

    /**
     * @see AdvancedQueueInterface::peek()
     */
    public function peek($limit = 1, $skip = 0)
    {
        try {
            $tasks = $this->queue->peek($limit, $skip);
        } catch (\Exception $e) {
            $this->logger->err(sprintf('Unable to peek task(s): %s.', $e->getMessage()));
            throw $e;
        }

        return $tasks;
    }

    /**
     * @see AdvancedQueueInterface::count()
     */
    public function count()
    {
        return $this->queue->count();
    }

    /**
     * @see AdvancedQueueInterface::clear()
     */
    public function clear()
    {
        try {
            $this->queue->clear();
        } catch (\Exception $e) {
            $this->logger->err(sprintf('Unable to clear queue: %s.', $e->getMessage()));
            throw $e;
        }

        $this->logger->debug('Queue was successfully cleared.');
    }
}