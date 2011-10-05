<?php

namespace TaskQueue\Tests\Queue\Database\Pdo;

use TaskQueue\Tests\Queue\AbstractQueueTest;
use TaskQueue\Queue\Database\Pdo\PostgresPdoQueue;
use TaskQueue\Task\Task;

class PostgresPdoQueueTest extends AbstractQueueTest
{
    protected $conn;

    public function setUp()
    {
        parent::setUp();

        $this->conn = PdoTestUtil::createConnection();
        $this->conn->exec('DROP TABLE IF EXISTS task_queue');
        $this->conn->exec('CREATE TABLE task_queue (id SERIAL, eta timestamp NOT NULL, task text NOT NULL)');
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->conn->exec('DROP TABLE IF EXISTS task_queue');
        $this->conn = null;
    }

    public function createQueue()
    {
        $this->conn->exec('TRUNCATE task_queue RESTART IDENTITY');

        return new PostgresPdoQueue($this->conn, 'task_queue');
    }
}