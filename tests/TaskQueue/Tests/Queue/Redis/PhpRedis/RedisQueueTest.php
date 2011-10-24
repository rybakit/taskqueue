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

        self::$conn->delete('task_queue_tests');
        self::$conn->close();
        self::$conn = null;
    }

    public function setUp()
    {
        parent::setUp();

        self::$conn->delete('task_queue_tests');
    }

    public static function createConnection()
    {
        $host = isset($GLOBALS['redis_host']) ? $GLOBALS['redis_host'] : '127.0.0.1';
        $port = isset($GLOBALS['redis_port']) ? $GLOBALS['redis_port'] : 6379;

        $redis = new \Redis();
        $redis->connect($host, $port);

        return $redis;
    }

    protected function createQueue()
    {
        $prefix = isset($GLOBALS['redis_prefix']) ? $GLOBALS['redis_prefix'] : 'task_queue_tests';

        return new RedisQueue(self::$conn, $prefix);
    }

    public function testPeek() {}
}