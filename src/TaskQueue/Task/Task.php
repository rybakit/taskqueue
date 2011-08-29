<?php

namespace Rybakit\TaskQueue\Task;

use Rybakit\TaskQueue\DataMapper\ExtractorInterface;
use Rybakit\TaskQueue\DataMapper\InjectorInterface;
use Rybakit\TaskQueue\Scheduler\SchedulerInterface;

class Task implements TaskInterface, ExtractorInterface, InjectorInterface
{
    /**
     * An unique identifier for the task.
     *
     * @var mixed
     */
    protected $id;

    /**
     * The task name.
     *
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $payload;

    /**
     * The estimated execution time of the task.
     *
     * @var \DateTime
     */
    protected $eta;

    /**
     * The maximum number of retries for this task.
     *
     * @var int
     */
    protected $maxRetryCount = 1;

    /**
     * The time interval, in seconds, between task retries.
     *
     * @var int
     */
    protected $retryDelay = 5;

    /**
     * The number of times this task has been retried.
     *
     * @var int
     */
    protected $retryCount = 0;

    /**
     * Constructor.
     *
     * @param string $name
     * @param mixed $payload
     * @param \DateTime|string|null $eta
     */
    public function __construct($name, $payload = null, $eta = null)
    {
        $this->name = $name;
        $this->payload = $payload;

        if ($eta) {
            $this->setEta($eta);
        }
    }

    /**
     * Sets an unique identifier for the task.
     *
     * @param mixed $id
     *
     * @throws \LogicException
     */
    public function setId($id)
    {
        $this->id = $id;

        /*
        if (!$this->id) {
            $this->id = $id;
        } else if ($this->id != $id) {
            throw new \LogicException(sprintf('You cannot modify task identifier (%s).', json_encode($this->id)));
        }
        */
    }

    /**
     * Gets an unique identifier for the task.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets task name.
     *
     * @param string $name
     *
     * @throws \LogicException
     */
    public function setName($name)
    {
        $this->name = $name;
        /*
        if (!$this->name) {
            $this->name = $name;
        } else if ($this->name != $name) {
            throw new \LogicException(sprintf('You cannot modify task name (%s).', $this->name));
        }
        */
    }

    /**
     * @see TaskInterface::getName()
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets task payload.
     *
     * @param mixed $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Returns task payload.
     *
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Sets earliest time when task will execute.
     *
     * @param \DateTime|string $eta
     *
     * @throws \InvalidArgumentException
     */
    public function setEta($eta)
    {
        if (is_string($eta)) {
            $eta = new \DateTime($eta);
        } else if (!$eta instanceof \DateTime) {
            throw new \InvalidArgumentException('$eta must be a string or \DateTime instance.');
        }

        $this->eta = $eta;
    }

    /**
     * Returns the earliest time when task will execute.
     *
     * @return \DateTime|null
     */
    public function getEta()
    {
        return $this->eta;
    }

    /**
     * @param int $count
     */
    public function setMaxRetryCount($count)
    {
        $this->maxRetryCount = $count;
    }

    /**
     * @return int
     */
    public function getMaxRetryCount()
    {
        return $this->maxRetryCount;
    }

    /**
     * @param int $delay
     */
    public function setRetryDelay($delay)
    {
        $this->retryDelay = $delay;
    }

    /**
     * @return int
     */
    public function getRetryDelay()
    {
        return $this->retryDelay;
    }

    /**
     * @param int $count
     */
    public function setRetryCount($count)
    {
        $this->retryCount = $count;
    }

    /**
     * @return int
     */
    public function getRetryCount()
    {
        return $this->retryCount;
    }

    /**
     * TODO think over returned result - store it within task or bypass?
     *
     * @see TaskInterface::run()
     */
    public function run($callback)
    {
        return call_user_func($callback, $this);
    }

    /**
     * @see TaskInterface::reschedule()
     */
    public function reschedule()
    {
        if ($this->maxRetryCount && $this->retryCount >= $this->maxRetryCount) {
            return false;
        }

        $this->eta = new \DateTime(sprintf('+%d seconds', $this->retryDelay));
        $this->retryCount++;

        return true;
    }

    /**
     * @see TaskInterface::__toString()
     */
    public function __toString()
    {
        $str = $this->name;

        if ($this->id) {
            $str .= '@'.$this->id;
        }

        return is_string($str) ? $str : spl_object_hash($this);
    }

    /**
     * @see ExtractorInterface::extract()
     */
    public function extract()
    {
        return array(
            'id'                => $this->id,
            'name'              => $this->name,
            'payload'           => $this->payload,
            'eta'               => $this->eta,
            'max_retry_count'   => $this->maxRetryCount,
            'retry_delay'       => $this->retryDelay,
            'retry_count'       => $this->retryCount,
        );
    }

    /**
     * @see InjectorInterface::inject()
     */
    public function inject(array $data)
    {
        if (isset($data['id'])) {
            $this->setId($data['id']);
        }
        if (isset($data['name'])) {
            $this->setName($data['name']);
        }
        if (isset($data['payload'])) {
            $this->setPayload($data['payload']);
        }
        if (isset($data['eta'])) {
            $this->setEta($data['eta']);
        }
        if (isset($data['max_retry_count'])) {
            $this->setMaxRetryCount($data['max_retry_count']);
        }
        if (isset($data['retry_delay'])) {
            $this->setRetryDelay($data['retry_delay']);
        }
        if (isset($data['retry_count'])) {
            $this->setRetryCount($data['retry_count']);
        }
    }
}