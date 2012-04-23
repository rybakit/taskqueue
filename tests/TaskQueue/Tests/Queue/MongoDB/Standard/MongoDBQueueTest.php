<?php

namespace TaskQueue\Tests\Queue\MongoDB\Standard;

use TaskQueue\Tests\Queue\AbstractQueueTest;
use TaskQueue\Queue\MongoDB\Standard\MongoDBQueue;

class MongoDBQueueTest extends AbstractQueueTest
{
    /**
     * @var \MongoDb
     */
    protected static $conn;

    public static function setUpBeforeClass()
    {
        if (!class_exists('\Mongo')) {
            return;
        }

        parent::setUpBeforeClass();

        self::$conn = self::createConnection();
        self::$conn->task_queue->drop();
        self::$conn->createCollection('task_queue');
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        self::$conn->task_queue->drop();
        self::$conn = null;
    }

    protected function setUp()
    {
        if (!self::$conn) {
            $this->markTestSkipped('MongoDBQueue requires the php "mongo" extension.');
        }

        parent::setUp();

        self::$conn->task_queue->remove(array(), array('safe' => true));
    }

    protected function createQueue()
    {
        return new MongoDBQueue(self::$conn->task_queue);
    }

    protected static function createConnection()
    {
        $server = isset($GLOBALS['mongo_server']) ? $GLOBALS['mongo_server'] : 'mongodb://localhost:27017';
        $dbName = isset($GLOBALS['mongo_db_name']) ? $GLOBALS['mongo_db_name'] : 'task_queue_tests';
        $mongo = new \Mongo($server);

        return  $mongo->selectDb($dbName);
    }
}