<?php

namespace TaskQueue\Tests\Queue\MongoDB\Standard;

use TaskQueue\Tests\Queue\AbstractQueueTest;
use TaskQueue\Queue\MongoDB\Standard\MongoDBQueue;

class MongoDBQueueTest extends AbstractQueueTest
{
    /**
     * @var \MongoDb
     */
    protected $conn;

    public function setUp()
    {
        parent::setUp();

        $this->conn = self::createConnection();
        $this->conn->task_queue->drop();
        $this->conn->createCollection('task_queue');
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->conn->task_queue->drop();
        $this->conn = null;
    }

    protected function createQueue()
    {
        $this->conn->task_queue->remove(array(), array('safe' => true));

        return new MongoDBQueue($this->conn->task_queue);
    }

    protected static function createConnection()
    {
        $server = isset($GLOBALS['mongo_server']) ? $GLOBALS['mongo_server'] : 'mongodb://localhost:27017';
        $dbName = isset($GLOBALS['mongo_db_name']) ? $GLOBALS['mongo_db_name'] : 'task_queue_tests';
        $mongo = new \Mongo($server);

        return  $mongo->selectDb($dbName);
    }
}