<?php

namespace TaskQueue\Worker;

use TaskQueue\Queue\QueueInterface;
use TaskQueue\Task\TaskInterface;
use TaskQueue\Log\LoggerInterface;
use TaskQueue\Log\NullLogger;

abstract class Worker
{
    /**
     * @var array
     */
    protected $queues = array();

    /**
     * @var \TaskQueue\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \TaskQueue\Queue\QueueInterface
     */
    protected $currentQueue;

    /**
     * @var \TaskQueue\Task\TaskInterface
     */
    protected $currentTask;

    /**
     * @var bool
     */
    protected $isCurrentTaskProcessed = false;

    /**
     * Constructor.
     *
     * @param array $queues
     * @param \TaskQueue\Log\LoggerInterface|null $logger
     */
    public function __construct(array $queues = array(), LoggerInterface $logger = null)
    {
        foreach ($queues as $queue) {
            $this->attachQueue($queue);
        }

        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Attaches a queue to worker.
     *
     * @param \TaskQueue\Queue\QueueInterface $queue
     */
    public function attachQueue(QueueInterface $queue)
    {
        $oid = spl_object_hash($queue);
        $this->queues[$oid] = $queue;
    }

    /**
     * Detaches a queue from worker.
     *
     * @param \TaskQueue\Queue\QueueInterface $queue
     */
    public function detachQueue(QueueInterface $queue)
    {
        $oid = spl_object_hash($queue);
        unset($this->queues[$oid]);
    }

    public function work()
    {
        register_shutdown_function(array($this, 'shutdown'));

        $next = false;

        try {
            $next = $this->getNext();
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            throw $e;
        }

        if (is_array($next)) {
            list($queue, $task) = $next;

            $this->currentQueue = $queue;
            $this->currentTask = $task;
            $this->isCurrentTaskProcessed = false;

            try {
                $this->runTask($task, $queue);
                $this->isCurrentTaskProcessed = true;

                $this->logger->info(sprintf('Task %s was successfully executed.', $task));
            } catch (\Exception $e) {
                $this->logger->err(sprintf('An error occurred while executing task %s: %s.', $task, $e->getMessage()));
                if ($task->reschedule()) {
                    $queue->push($task);
                    $this->isCurrentTaskProcessed = true;
                } else {
                    $this->logger->err(sprintf('Task %s failed.', $task));
                }
            }

            $this->currentTask = null;
            $this->currentQueue = null;
        }
    }

    public function shutdown()
    {
        if (!$this->currentTask) {
            return;
        }

        if ($err = error_get_last()) {
            $this->logger->err(sprintf('Worker died while working on task %s. Last error "%s" occurred in %s on line %d.',
                $this->currentTask, $err['message'], $err['file'], $err['line']));
        }

        if (!$this->isCurrentTaskProcessed) {
            if ($this->currentTask->reschedule()) {
                $this->currentQueue->push($this->currentTask);
            } else {
                $this->logger->err(sprintf('Task %s failed.', $this->currentTask));
            }
        }
    }

    public function __toString()
    {
        return sprintf('#%s (%s)', getmypid(), php_uname());
    }

    /**
     * @throws \UnexpectedValueException
     *
     * @return array|bool
     */
    protected function getNext()
    {
        foreach ($this->queues as $queue) {
            while ($task = $queue->pop()) {
                if (!$task instanceof TaskInterface) {
                    throw new \UnexpectedValueException(sprintf('Result of %s::pop() must be an instance of TaskInterface.', get_class($queue)));
                }
                return array($queue, $task);
            }
        }

        return false;
    }

    abstract protected function runTask(TaskInterface $task, QueueInterface $queue);
}