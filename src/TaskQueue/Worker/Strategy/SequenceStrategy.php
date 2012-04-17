<?php

namespace TaskQueue\Worker\Strategy;

use TaskQueue\Queue\QueueInterface;

class SequenceStrategy implements StrategyInterface
{
    /**
     * @var array
     */
    protected $queues = array();

    public function __construct(array $queues = array())
    {
        foreach ($queues as $queue) {
            $this->addQueue($queue);
        }
    }

    /**
     * Adds a queue to worker.
     *
     * @param \TaskQueue\Queue\QueueInterface $queue
     */
    public function addQueue(QueueInterface $queue)
    {
        $oid = spl_object_hash($queue);
        $this->queues[$oid] = $queue;
    }

    /**
     * Removes a queue from worker.
     *
     * @param \TaskQueue\Queue\QueueInterface $queue
     */
    public function removeQueue(QueueInterface $queue)
    {
        $oid = spl_object_hash($queue);
        unset($this->queues[$oid]);
    }

    public function getNext()
    {
        foreach ($this->queues as $queue) {
            if ($task = $queue->pop()) {
                return $task;
            }
        }

        return false;
    }
}