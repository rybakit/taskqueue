<?php

namespace TaskQueue\Backend\Database\Pdo;

use TaskQueue\TaskQueueInterface;
use TaskQueue\Task\TaskInterface;

class PdoBackend implements TaskQueueInterface
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var \PDO
     */
    protected $db;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * Constructor.
     *
     * @param \PDO $db
     * @param string $tableName
     */
    public function __construct(\PDO $db, $tableName)
    {
        $this->db = $db;
        $this->tableName = (string) $tableName;
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @see TaskQueueInterface::push()
     */
    public function push(TaskInterface $task)
    {
        $sql = 'INSERT INTO '.$this->tableName.' (eta, task) VALUES (:eta, :task)';

        $stmt = $this->db->prepare($sql);
        $eta = $task->getEta() ?: new \DateTime();
        $stmt->bindValue(':eta', $eta->format(self::DATETIME_FORMAT), \PDO::PARAM_STR);
        $stmt->bindValue(':task', $this->normalizeData($task), \PDO::PARAM_STR);

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }
    }

    /**
     * @see TaskQueueInterface::pop()
     */
    public function pop()
    {
        $sql = 'SELECT task FROM '.$this->tableName.' WHERE eta <= :now ORDER BY eta, id LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':now', date(self::DATETIME_FORMAT));

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }

        $data = $stmt->fetchColumn();

        return $data ? $this->normalizeData($data, true) : false;
    }

    /**
     * @see TaskQueueInterface::peek()
     */
    public function peek($limit = 1, $skip = 0)
    {
        $sql = 'SELECT task FROM '.$this->tableName.' WHERE eta <= :now ORDER BY eta, id';

        if ($limit) {
            $sql .= ' LIMIT '.(int) $limit;
        }
        if ($skip) {
            $sql .= ' OFFSET '.(int) $skip;
        }

        $stmt = $this->db->prepare($sql);
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
     * @param mixed $data
     * @param bool $invert
     *
     * @return array
     */
    public function normalizeData($data, $invert = false)
    {
        return $invert ? unserialize(base64_decode($data)) : base64_encode(serialize($data));
    }
}