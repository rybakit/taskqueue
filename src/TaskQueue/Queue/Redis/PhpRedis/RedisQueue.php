<?php

namespace TaskQueue\Queue\Redis\PhpRedis;

use TaskQueue\Queue\AdvancedQueueInterface;
use TaskQueue\Task\TaskInterface;
use TaskQueue\SimpleSerializer;

class RedisQueue implements AdvancedQueueInterface
{
    const LOCK_TIMEOUT = 10;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var \TaskQueue\SimpleSerializer
     */
    protected $serializer;

    /**
     * Constructor.
     *
     * @param \Redis $redis
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
        $this->serializer = new SimpleSerializer();
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
        $member = $unique.'@'.$this->serializer->serialize($task);

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

                $data = substr($key, strpos($key, '@') + 1);

                return $this->serializer->unserialize($data);
            }

            // the lock failed to be released by the client
             $this->releaseLock();
        }
    }

    /**
     * @see AdvancedQueueInterface::peek()
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

        $serializer = $this->serializer;
        return new IterableResult($range, function ($data) use ($serializer) {
            $data = substr($data, strpos($data, '@') + 1);
            return $serializer->unserialize($data);
        });
    }

    /**
     * @see AdvancedQueueInterface::count()
     */
    public function count()
    {
        return $this->redis->zCard('tasks');
    }

    /**
     * @see AdvancedQueueInterface::clear()
     */
    public function clear()
    {
        $this->redis->del(array('tasks', 'sequence'));
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