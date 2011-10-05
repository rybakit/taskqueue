<?php

namespace TaskQueue\Tests\Queue\Database\Pdo;

use TaskQueue\Tests\Queue\AbstractQueueTest;
use TaskQueue\Queue\Database\Pdo\PostgresPdoQueue;

class PostgresPdoQueueTest extends AbstractQueueTest
{
    protected $conn;

    public function setUp()
    {
        parent::setUp();

        $this->conn = self::createConnection();
        $this->conn->exec('DROP TABLE IF EXISTS task_queue');
        $this->conn->exec('CREATE TABLE task_queue (id SERIAL, eta timestamp NOT NULL, task text NOT NULL)');
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->conn->exec('DROP TABLE IF EXISTS task_queue');
        $this->conn = null;
    }

    protected function createQueue()
    {
        $this->conn->exec('TRUNCATE task_queue RESTART IDENTITY');

        return new PostgresPdoQueue($this->conn, 'task_queue');
    }

    protected static function createConnection()
    {
        /*
        if (isset($GLOBALS['db_pg_host'], $GLOBALS['db_port'], $GLOBALS['db_pg_username'],
                  $GLOBALS['db_password'], $GLOBALS['db_name'])) {
        */
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s',
            $GLOBALS['db_pg_host'],
            $GLOBALS['db_pg_port'],
            $GLOBALS['db_pg_db_name']);

        return new \Pdo($dsn, $GLOBALS['db_pg_username'], $GLOBALS['db_pg_password']);
    }
}