<?php

namespace TaskQueue\Tests\Queue\MongoDB\Standard;

use TaskQueue\Tests\Queue\AbstractQueueTest;
use TaskQueue\Queue\MongoDB\Standard\MongoDBQueue;

class MongoDBQueueTest extends AbstractQueueTest
{
    protected $collection;

    public function setUp()
    {
        parent::setUp();

        $this->collection = self::createConnection()->task_queue;
        $this->collection->remove(array(), array('safe' => true));
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->collection->drop();
        $this->collection = null;
    }

    protected function createQueue()
    {
        $this->collection->remove(array(), array('safe' => true));

        return new MongoDBQueue($this->collection);
    }

    protected static function createConnection()
    {
        $server = isset($GLOBALS['mongo_server']) ? $GLOBALS['mongo_server'] : null;
        $dbName = isset($GLOBALS['mongo_db_name']) ? $GLOBALS['mongo_db_name'] : 'task_queue_tests';
        $mongo = new \Mongo($server);

        return  $mongo->selectDb($dbName);
    }
}