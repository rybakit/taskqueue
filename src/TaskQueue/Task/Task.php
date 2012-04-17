<?php

namespace TaskQueue\Task;

class Task implements TaskInterface
{
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
     * @param mixed $payload
     * @param \DateTime|string|null $eta
     */
    public function __construct($payload, $eta = null)
    {
        $this->payload = $payload;

        if ($eta) {
            $this->setEta($eta);
        }
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
     * @see TaskInterface::getEta()
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
     * @return int
     */
    public function getRetryCount()
    {
        return $this->retryCount;
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
        $payload = $this->payload;

        if (is_object($payload)) {
            $payload = get_class($payload);
        } else if (!is_string($this->payload)) {
            $payload = preg_replace("/\n\s*/s", '', var_export($payload, true));
        }

        return sprintf('%s {%s}', get_class($this), $payload);
    }

    /**
     * Clones this object.
     */
    public function __clone()
    {
        if ($this->eta) {
            $this->eta = clone $this->eta;
        }

        $this->retryCount = 0;
    }
}