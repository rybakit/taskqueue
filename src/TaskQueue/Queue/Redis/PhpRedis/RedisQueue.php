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
     * Constructor.
     *
     * @param \Redis $redis
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
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
     * @see QueueInterface::push()
     */
    public function push(TaskInterface $task)
    {
        $eta = $task->getEta() ?: new \DateTime();

        $score = $eta->getTimestamp();
        $unique = $this->redis->incr('sequence');
        $member = $unique.'@'.$this->normalizeData($task);

        $result = $this->redis->zAdd('tasks', $score, $member);
        if (!$result) {
            throw new \RuntimeException(sprintf('Unable to push the task %s.', $task));
        }
    }

    /**
     * @see QueueInterface::pop()
     */
    public function pop()
    {
        while (true) {
            if ($this->tryLock()) {
                $range = $this->redis->zRangeByScore('tasks', '-inf', time(), array('limit' => array(0, 1)));
                if (empty($range)) {
                    $this->releaseLock();
                    return false;
                }

                $key = reset($range);
                $this->redis->zRem('tasks', $key);
                $this->releaseLock();

                $data = substr($key, strpos($key, '@'));

                return $this->normalizeData($data, true);
            }

            // the lock failed to be released by the client
             $this->releaseLock();
        }
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

        $range = $this->redis->zRangeByScore('tasks', '-inf', time(), array('limit' => array($skip, $limit)));
        if (empty($range)) {
            return false;
        }
    }

    /**
     * @see QueueInterface::count()
     */
    public function count()
    {
        return $this->redis->zCard('tasks');
    }

    /**
     * @see QueueInterface::clear()
     */
    public function clear()
    {
        $this->redis->del(array('tasks', 'sequence'));
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

    protected function tryLock()
    {
        $this->redis->watch('lock');
        $result = $this->redis->blPop(array('lock'), static::LOCK_TIMEOUT);

        return !empty($result);
    }

    protected function releaseLock()
    {
        $this->redis->multi()
            ->del('lock')
            ->lPush('lock', 1)
            ->exec();
    }
}