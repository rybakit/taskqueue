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
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s',
            isset($GLOBALS['db_pg_host']) ? $GLOBALS['db_pg_host'] : 'localhost',
            isset($GLOBALS['db_pg_port']) ? $GLOBALS['db_pg_port'] : '5432',
            isset($GLOBALS['db_pg_db_name']) ? $GLOBALS['db_pg_db_name'] : 'task_queue_tests',
            isset($GLOBALS['db_pg_username']) ? $GLOBALS['db_pg_username'] : 'postgres',
            isset($GLOBALS['db_pg_password']) ? $GLOBALS['db_pg_password'] : ''
        );

        return new \Pdo($dsn);
    }
}