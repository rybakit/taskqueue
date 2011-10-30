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
    /*
    public function pop()
    {
        while (true) {
            $expires = time() + static::LOCK_TIMEOUT;
            $acquired = $this->redis->setNx($this->prefix.':lock', $expires);
            if (!$acquired) {
                $timeout = $this->redis->get($this->prefix.':lock');
                if ($timeout >= time()) {
                    //echo "get()\n";
                    usleep(100);
                    continue;
                }

                $timeout = $this->redis->getSet($this->prefix.':lock', $expires);
                if ($timeout >= time()) {
                    //echo "getSet()\n";
                    usleep(100);
                    continue;
                }
            }

            $range = $this->redis->zRangeByScore($this->prefix.':tasks', '-inf', time(),
                array('limit' => array(0, 1)));

            if (empty($range)) {
                // release lock
                if ($this->redis->get($this->prefix.':lock') == $expires) {
                    $this->redis->del($this->prefix.':lock');
                }
                return false;
            }

            $key = reset($range);
            //echo $key, "\n";
            if ($this->redis->zRem($this->prefix.':tasks', $key)) {
                // release lock
                if ($this->redis->get($this->prefix.':lock') == $expires) {
                    $this->redis->del($this->prefix.':lock');
                }
            } else {
                throw new \RuntimeException(sprintf('Key %s is not found.', $key));
            }

            $data = substr($key, strpos($key, '@'));

            return $this->normalizeData($data, true);
        }
    }
    */

    public function pop()
    {
        while (true) {
            if ($this->tryLock()) {
                $range = $this->redis->zRangeByScore($this->prefix.':tasks', '-inf', time(),
                    array('limit' => array(0, 1)));

                if (empty($range)) {
                    $this->releaseLock();
                    return false;
                }

                $key = reset($range);
                $this->redis->zRem($this->prefix.':tasks', $key);
                $this->releaseLock();

                $data = substr($key, strpos($key, '@'));

                return $this->normalizeData($data, true);
            }

            // the lock failed to be released by the client
             $this->releaseLock();
        }
    }

    /*
    public function pop()
    {
        $max = time();
        $i = 0;

        while (true) {
            $range = $this->redis->zRangeByScore($this->prefix.':tasks', '-inf', $max,
                array('limit' => array(0, 15)));

            if (empty($range)) {
                return false;
            }

            foreach ($range as $key) {
                if ($this->redis->zRem($this->prefix.':tasks', $key)) {
                    break 2;
                }
                $i++;
                //echo "$i call zRem()\n";
            }
            //echo "call zRangeByScore()\n";
        };

        //echo "Success! (total iterations: $i)\n\n";

        $data = substr($key, strpos($key, '@'));

        return $this->normalizeData($data, true);
    }
    */

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
            array('limit' => array($skip, $limit)));

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

    protected function tryLock()
    {
        $this->redis->watch($this->prefix.':lock');
        $result = $this->redis->blPop(array($this->prefix.':lock'), static::LOCK_TIMEOUT);

        return !empty($result);
    }

    protected function releaseLock()
    {
        $this->redis->multi()
            ->del($this->prefix.':lock')
            ->lPush($this->prefix.':lock', 1)
            ->exec();
    }
}