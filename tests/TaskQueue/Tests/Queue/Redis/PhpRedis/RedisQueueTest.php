<?php

namespace TaskQueue\Tests\Queue\Redis\PhpRedis;

use TaskQueue\Tests\Queue\AbstractQueueTest;
use TaskQueue\Queue\Redis\PhpRedis\RedisQueue;

class RedisQueueTest extends AbstractQueueTest
{
    /**
     * @var \Redis
     */
    protected static $conn;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$conn = self::createConnection();
        //self::$conn->task_queue->drop();
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        self::$conn->del(self::$conn->getOption(\Redis::OPT_PREFIX));
        self::$conn->close();
        self::$conn = null;
    }

    public function setUp()
    {
        parent::setUp();

        self::$conn->del(self::$conn->getOption(\Redis::OPT_PREFIX));
    }

    public static function createConnection()
    {
        $host = isset($GLOBALS['redis_host']) ? $GLOBALS['redis_host'] : '127.0.0.1';
        $port = isset($GLOBALS['redis_port']) ? $GLOBALS['redis_port'] : 6379;
        $prefix = isset($GLOBALS['redis_prefix']) ? $GLOBALS['redis_prefix'] : 'task_queue_tests:';

        $redis = new \Redis();
        $redis->setOption(\Redis::OPT_PREFIX, $prefix);
        $redis->connect($host, $port);

        return $redis;
    }

    protected function createQueue()
    {
        return new RedisQueue(self::$conn);
    }

    public function testPeek() {}
}