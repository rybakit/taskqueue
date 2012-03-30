<?php

namespace TaskQueue\Queue\Database\Pdo;

use TaskQueue\Queue\AdvancedQueueInterface;
use TaskQueue\Task\TaskInterface;

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
     * Constructor.
     *
     * @param \PDO $conn
     * @param string $tableName
     */
    public function __construct(\PDO $conn, $tableName)
    {
        $this->conn = $conn;
        $this->tableName = (string) $tableName;
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
        $stmt->bindValue(':eta', $eta->format(self::DATETIME_FORMAT));
        $stmt->bindValue(':task', $this->normalizeData($task));

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
        $stmt->bindValue(':now', date(self::DATETIME_FORMAT));

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }

        $data = $stmt->fetchColumn();

        return $data ? $this->normalizeData($data, true) : false;
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
        $stmt->bindValue(':now', date(self::DATETIME_FORMAT));

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }

        $self = $this;
        return new IterableResult(function() use ($stmt, $self) {
            $data = $stmt->fetchColumn();
            return $data ? $self->normalizeData($data, true) : false;
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
     * @param mixed $data
     * @param bool $invert
     *
     * @return array
     */
    public function normalizeData($data, $invert = false)
    {
        return $invert ? unserialize(base64_decode($data)) : base64_encode(serialize($data));
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