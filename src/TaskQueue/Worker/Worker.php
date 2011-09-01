<?php

namespace TaskQueue\Worker;

use TaskQueue\TaskQueueInterface;
use TaskQueue\Log\LoggerInterface;
use TaskQueue\Log\NullLogger;
use TaskQueue\Task\TaskInterface;

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
     * @var \TaskQueue\TaskQueueInterface
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
     * @var bool
     */
    protected $shutdown = false;

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
     * @param \TaskQueue\TaskQueueInterface $queue
     */
    public function attachQueue(TaskQueueInterface $queue)
    {
        $oid = spl_object_hash($queue);
        $this->queues[$oid] = $queue;
    }

    /**
     * Detaches a queue from worker.
     *
     * @param \TaskQueue\TaskQueueInterface $queue
     */
    public function detachQueue(TaskQueueInterface $queue)
    {
        $oid = spl_object_hash($queue);
        unset($this->queues[$oid]);
    }

    /**
     * Processes attached queues.
     *
     * @param int $interval Number of seconds the worker will wait until processing the next task. Default is 10.
     */
    public function work($interval = 10)
    {
        $this->startup();
        $this->logger->info(sprintf('Worker %s started.', $this));

        while (true) {
            if ($this->shutdown) {
                $this->logger->info(sprintf('Worker %s shutdown.', $this));
                break;
            }

            $next = false;
            try {
                $next = $this->getNext();
            } catch (\Exception $e) {
                $this->logger->err($e->getMessage());
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

            sleep($interval);
        }

        $this->logger->info(sprintf('Worker %s stopped.', $this));
    }

    public function shutdown()
    {
        $this->shutdown = true;
    }

    /**
     * TODO change scope to protected (use closure in register_shutdown_function())
     */
    public function failure()
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

    protected function startup()
    {
        register_shutdown_function(array($this, 'failure'));
        $this->registerSigHandlers();
    }

    /**
     * Registers signal handlers that a worker should respond to.
     *
     * TERM: Shutdown immediately and stop processing jobs.
     * INT: Shutdown immediately and stop processing jobs.
     * QUIT: Shutdown after the current job finishes processing.
     */
    protected function registerSigHandlers()
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        declare(ticks = 1);
        pcntl_signal(SIGTERM, array($this, 'shutdown'));
        pcntl_signal(SIGINT,  array($this, 'shutdown'));
        pcntl_signal(SIGQUIT, array($this, 'shutdown'));

        $this->logger->debug('Registered signals.');
    }

    abstract protected function runTask(TaskInterface $task, TaskQueueInterface $queue);
}