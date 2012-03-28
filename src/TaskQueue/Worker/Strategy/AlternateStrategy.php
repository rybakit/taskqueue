<?php

namespace TaskQueue\Worker\Strategy;

use TaskQueue\Queue\QueueInterface;

class AlternateStrategy implements StrategyInterface
{
    /**
     * @var array
     */
    protected $queues = array();

    protected $nextPos = 0;

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
        if ($count = count($this->queues)) {
            $it = new \ArrayIterator($this->queues);
            $it = new \InfiniteIterator($it);
            $it = new \LimitIterator($it, $this->nextPos, $count);

            foreach ($it as $queue) {
                if ($task = $queue->pop()) {
                    $this->nextPos = ($it->getPosition() + 1) % $count;
                    return $task;
                }
            }
        }

        return false;
    }
}