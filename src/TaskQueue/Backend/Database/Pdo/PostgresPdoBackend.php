<?php

namespace Rybakit\TaskQueue\Backend\Database\Pdo;

use PDO;
use Rybakit\TaskQueue\DataMapper\DataMapperInterface;
use Rybakit\TaskQueue\Task\Task;

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
        $sql = sprintf('INSERT INTO %s (%s) VALUES (:%s) RETURNING id',
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
        $this->dataMapper->inject($task, $data);
    }

    /**
     * @see TaskQueueInterface::pop()
     */
    public function pop(array $taskNames = array())
    {
        $where = array();
        if ($taskNames) {
            foreach ($taskNames as $i => $taskName) {
                $where[':name_'.$i] = $taskName;
            }
        }

        $sql = '
            DELETE FROM '.$this->tableName.' WHERE id = (
                SELECT id
                FROM '.$this->tableName.'
                WHERE '.($where ? ' AND name IN ('.implode(', ', array_keys($where)).') AND ' : '').' (eta <= :now OR eta IS NULL)
                ORDER BY eta NULLS FIRST, id
                LIMIT 1
            ) RETURNING id, name, payload, eta, max_retry_count, retry_delay, retry_count, _task_class';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':now', date(self::DATETIME_FORMAT));
        foreach ($where as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

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