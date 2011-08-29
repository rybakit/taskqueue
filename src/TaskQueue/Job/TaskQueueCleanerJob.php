<?php

namespace Rybakit\TaskQueue\Job;

use Rybakit\TaskQueue\TaskQueueInterface;
use Rybakit\TaskQueue\TaskQueueAwareInterface;
use Rybakit\TaskQueue\Task\Task;

class TaskQueueCleanerJob implements TaskQueueAwareInterface, JobInterface
{
    /**
     * @var \Rybakit\TaskQueue\TaskQueueInterface
     */
    protected $taskQueue;

    /**
     * @var array
     */
    protected $criteria = array();

    /**
     * Constructor.
     *
     * @param array $criteria
     */
    public function __construct(array $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * @see TaskQueueAwareInterface::setTaskQueue()
     */
    public function setTaskQueue(TaskQueueInterface $taskQueue)
    {
        $this->taskQueue = $taskQueue;
    }

    /**
     * @see JobInterface::execute()
     */
    public function execute()
    {
        $this->taskQueue->removeBy($this->criteria);
    }
}