<?php

namespace Rybakit\TaskQueue\Worker;

use Rybakit\TaskQueue\TaskQueueInterface;
use Rybakit\TaskQueue\Log\LoggerInterface;
use Rybakit\TaskQueue\Log\NullLogger;
use Rybakit\TaskQueue\Task\TaskInterface;

abstract class Worker
{
    /**
     * @var array
     */
    protected $queues = array();

    /**
     * @var \Rybakit\TaskQueue\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Rybakit\TaskQueue\TaskQueueInterface
     */
    protected $currentQueue;

    /**
     * @var \Rybakit\TaskQueue\Task\TaskInterface
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
     * @param \Rybakit\TaskQueue\Log\LoggerInterface|null $logger
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
     * @param \Rybakit\TaskQueue\TaskQueueInterface $taskQueue
     */
    public function attachQueue(TaskQueueInterface $taskQueue)
    {
        $this->queues[spl_object_hash($taskQueue)] = $taskQueue;
    }

    /**
     * @return array
     */
    public function filterTaskNames()
    {
        return array();
    }

    /**
     * Processes attached queues.
     *
     * @param int $interval Number of seconds the worker will wait until processing the next task. Default is 5.
     *
     * @throws \UnexpectedValueException
     */
    public function work($interval = 5)
    {
        register_shutdown_function(array($this, 'shutdown'));

        $this->logger->info(sprintf('Worker %s started.', $this));

        $taskNames = $this->filterTaskNames();
        foreach ($this->queues as $queue) {
            while ($task = $queue->pop($taskNames)) {
                if (!$task instanceof TaskInterface) {
                    throw new \UnexpectedValueException('Expected instance of TaskInterface.');
                }

                $this->currentQueue = $queue;
                $this->currentTask = $task;
                $this->isCurrentTaskProcessed = false;

                try {
                    $this->runTask($task);
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

                sleep($interval);
            }
        }

        $this->logger->info(sprintf('Worker %s stopped.', $this));
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
        return sprintf('%s (%s)', __FILE__, php_uname());
    }

    abstract protected function runTask(TaskInterface $task);
}