<?php

namespace TaskQueue\Backend\Database\Pdo;

use PDO;
use TaskQueue\DataMapper\DataMapperInterface;
use TaskQueue\Task\Task;

class PostgresPdoBackend extends PdoBackend
{
    public function __construct(PDO $db, $tableName, DataMapperInterface $dataMapper = null)
    {
        if ('pgsql' != $db->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            throw new \InvalidArgumentException('Invalid PDO driver specified.');
        }

        parent::__construct($db, $tableName, $dataMapper);
    }

    /**
     * @see TaskQueueInterface::push()
     */
    public function push($task)
    {
        $data = $this->dataMapper->extract($task);
        $data = $this->normalizeData($data);

        $data['_task_class'] = get_class($task);
        unset($data['id']);

        $columnNames = array_keys($data);
        $sql = sprintf('INSERT INTO %s (%s) VALUES (:%s) RETURNING id, eta',
            $this->tableName, implode(', ', $columnNames), implode(', :', $columnNames));

        $stmt = $this->db->prepare($sql);
        foreach ($data as $columnName => $value) {
            $stmt->bindValue(':'.$columnName, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $data = $this->normalizeData($data, true);

        $this->dataMapper->inject($task, $data);
    }

    /**
     * @see TaskQueueInterface::pop()
     */
    public function pop()
    {
        $sql = '
            DELETE FROM '.$this->tableName.' WHERE id = (
                SELECT id
                FROM '.$this->tableName.'
                WHERE eta <= :now
                ORDER BY eta, id
                LIMIT 1
            ) RETURNING id, payload, eta, max_retry_count, retry_delay, retry_count, _task_class';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':now', date(self::DATETIME_FORMAT));

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }

        if (!$data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $data = $this->normalizeData($data, true);

        return $this->dataMapper->inject($data['_task_class'], $data);
    }
}