<?php

namespace TaskQueue\Queue\Database\Pdo;

use TaskQueue\Queue\AdvancedQueueInterface;
use TaskQueue\Task\TaskInterface;
use TaskQueue\SimpleSerializer;

class PdoQueue implements AdvancedQueueInterface
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var \PDO
     */
    protected $conn;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var \TaskQueue\SimpleSerializer
     */
    protected $serializer;

    /**
     * Constructor.
     *
     * @param \PDO $conn
     * @param string $tableName
     */
    public function __construct(\PDO $conn, $tableName)
    {
        $this->conn = $conn;
        $this->tableName = (string) $tableName;
        $this->serializer = new SimpleSerializer();
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @see QueueInterface::push()
     */
    public function push(TaskInterface $task)
    {
        $sql = 'INSERT INTO '.$this->tableName.' (eta, task) VALUES (:eta, :task)';

        $stmt = $this->prepareStatement($sql);
        $eta = $task->getEta() ?: new \DateTime();
        $stmt->bindValue(':eta', $eta->format(static::DATETIME_FORMAT));
        $stmt->bindValue(':task', $this->serializer->serialize($task));

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }
    }

    /**
     * @see QueueInterface::pop()
     */
    public function pop()
    {
        $sql = 'SELECT task FROM '.$this->tableName.' WHERE eta <= :now ORDER BY eta, id LIMIT 1';

        $stmt = $this->prepareStatement($sql);
        $stmt->bindValue(':now', date(static::DATETIME_FORMAT));

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }

        $data = $stmt->fetchColumn();

        return $data ? $this->serializer->unserialize($data) : false;
    }

    /**
     * @see AdvancedQueueInterface::peek()
     */
    public function peek($limit = 1, $skip = 0)
    {
        if ($limit <= 0) {
            // Parameter limit must either be -1 or a value greater than or equal 0
            throw new \OutOfRangeException('Parameter limit must be greater than 0.');
        }
        if ($skip < 0) {
            throw new \OutOfRangeException('Parameter skip must be greater than or equal 0.');
        }

        $sql = 'SELECT task FROM '.$this->tableName.' WHERE eta <= :now ORDER BY eta, id';

        if ($limit) {
            $sql .= ' LIMIT '.(int) $limit;
        }
        if ($skip) {
            $sql .= ' OFFSET '.(int) $skip;
        }

        $stmt = $this->prepareStatement($sql);
        $stmt->bindValue(':now', date(static::DATETIME_FORMAT));

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }

        // $iterator = new \NoRewindIterator(new \IteratorIterator($stmt));
        $serializer = $this->serializer;
        return new IterableResult(function() use ($stmt, $serializer) {
            $data = $stmt->fetchColumn();
            return $data ? $serializer->unserialize($data) : false;
        });
    }

    /**
     * @see AdvancedQueueInterface::count()
     */
    public function count()
    {
        $sql = 'SELECT COUNT(*) FROM '.$this->tableName;
        $stmt = $this->prepareStatement($sql);

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }

        return $stmt->fetchColumn();
    }

    /**
     * @see AdvancedQueueInterface::clear()
     */
    public function clear()
    {
        $sql = 'TRUNCATE TABLE '.$this->tableName;
        $stmt = $this->prepareStatement($sql);

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }
    }

    /**
     * @param string $sql
     *
     * @return \PDOStatement
     *
     * @throws \RuntimeException
     */
    protected function prepareStatement($sql)
    {
        try {
            $stmt = $this->conn->prepare($sql);
        } catch (\Exception $e) {
            $stmt = false;
        }

        if (false === $stmt) {
            throw new \RuntimeException('The database cannot successfully prepare the statement.');
        }

        return $stmt;
    }
}