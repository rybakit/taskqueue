<?php

namespace TaskQueue\Queue\Redis\PhpRedis;

use TaskQueue\Queue\QueueInterface;
use TaskQueue\Task\TaskInterface;

class RedisQueue implements QueueInterface
{
    const LOCK_TIMEOUT = 10;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * Constructor.
     *
     * @param \Redis $redis
     * @param string $name
     */
    public function __construct(\Redis $redis, $prefix)
    {
        $this->redis = $redis;
        $this->prefix = $prefix;

        $this->initLock();
    }

    /**
     * Retrieves \Redis instance.
     *
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @see QueueInterface::push()
     */
    public function push(TaskInterface $task)
    {
        $eta = $task->getEta() ?: new \DateTime();

        $score = $eta->getTimestamp();
        $unique = $this->redis->incr($this->prefix.':sequence');
        $member = $unique.'@'.$this->normalizeData($task);

        $result = $this->redis->zAdd($this->prefix.':tasks', $score, $member);
        if (!$result) {
            throw new \RuntimeException(sprintf('Unable to push the task %s.', $task));
        }
    }

    /**
     * @see QueueInterface::pop()
     */
    public function pop()
    {
        if ($this->tryLock()) {
            $range = $this->redis->zRangeByScore($this->prefix.':tasks', '-inf', time(),
                array('limit' => array(0, 1)));

            if (empty($range)) {
                $this->unlock();
                return false;
            }

            $key = reset($range);
            $this->redis->zRem($this->prefix.':tasks', $key);
            $this->unlock();

            $data = substr($key, strpos($key, '@'));

            return $this->normalizeData($data, true);
        }

        return false;
    }

    /**
     * @see QueueInterface::peek()
     */
    public function peek($limit = 1, $skip = 0)
    {
        if ($limit <= 0) {
            // Parameter limit must either be -1 or a value greater than or equal 0
            throw new \OutOfRangeException('Parameter limit must be greater than 0.');
        }
        if ($skip < 0) {
            throw new \OutOfRangeException('Parameter skip must be greater than or equal 0.');
        }

        $range = $this->redis->zRangeByScore($this->prefix.':tasks', '-inf', time(),
            array('withscores' => true, 'limit' => array($skip, $limit)));

        if (empty($range)) {
            return false;
        }
    }

    /**
     * @see QueueInterface::count()
     */
    public function count()
    {
        return $this->redis->zCard($this->prefix.':tasks');
    }

    /**
     * @see QueueInterface::clear()
     */
    public function clear()
    {
        $this->redis->del(array(
            $this->prefix.':tasks',
            $this->prefix.':sequence',
        ));
    }

    /**
     * @param mixed $data
     * @param bool $invert
     *
     * @return array
     */
    public function normalizeData($data, $invert = false)
    {
        return $invert ? unserialize(base64_decode($data)) : base64_encode(serialize($data));
    }

    protected function initLock()
    {
        if (!$this->redis->getSet($this->prefix.':lock_init', 1)) {
            $this->redis->lPush($this->prefix.':lock', 1);
        }
    }

    protected function tryLock()
    {
        $result = $this->redis->blPop(array($this->prefix.':lock'), static::LOCK_TIMEOUT);

        return !empty($result);
    }

    protected function unlock()
    {
        $this->redis->lPush($this->prefix.':lock', 1);
    }
}