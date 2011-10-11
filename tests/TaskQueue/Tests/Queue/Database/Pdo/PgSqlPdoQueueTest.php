<?php

namespace TaskQueue\Tests\Queue\Database\Pdo;

use TaskQueue\Tests\Queue\AbstractQueueTest;
use TaskQueue\Queue\Database\Pdo\PgSqlPdoQueue;

class PgSqlPdoQueueTest extends AbstractQueueTest
{
    /**
     * @var \PDO
     */
    protected static $conn;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$conn = self::createConnection();
        self::$conn->exec('DROP TABLE IF EXISTS task_queue');
        self::$conn->exec('CREATE TABLE task_queue (id SERIAL, eta timestamp NOT NULL, task text NOT NULL)');
    }

    public static function tearDownAfterClass()
    {
        parent::tearDown();

        self::$conn->exec('DROP TABLE IF EXISTS task_queue');
        self::$conn = null;
    }

    public function setUp()
    {
        parent::setUp();

        self::$conn->exec('TRUNCATE task_queue RESTART IDENTITY');
    }

    protected function createQueue()
    {
        return new PgSqlPdoQueue(self::$conn, 'task_queue');
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