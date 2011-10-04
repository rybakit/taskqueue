<?php

namespace TaskQueue\Tests\Queue\Database\Pdo;

use TaskQueue\Queue\PhpQueue;
use TaskQueue\Task\Task;

class PdoTestUtil
{
    /**
     * @return \Pdo
     */
    public static function createConnection()
    {
        /*
        if (isset($GLOBALS['db_pg_host'], $GLOBALS['db_port'], $GLOBALS['db_pg_username'],
                  $GLOBALS['db_password'], $GLOBALS['db_name'])) {
        */
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s',
            $GLOBALS['db_pg_host'],
            $GLOBALS['db_pg_port'],
            $GLOBALS['db_pg_name']);

        return new \Pdo($dsn, $GLOBALS['db_pg_username'], null);
    }
}