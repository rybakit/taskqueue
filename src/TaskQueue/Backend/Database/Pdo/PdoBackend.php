<?php

namespace Rybakit\TaskQueue\Backend\Database\Pdo;

use PDO;
use Rybakit\TaskQueue\TaskQueueInterface;
use Rybakit\TaskQueue\DataMapper\DataMapperInterface;
use Rybakit\TaskQueue\DataMapper\DataMapper;
use Rybakit\TaskQueue\Task\Task;

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
     * @var \Rybakit\TaskQueue\DataMapper\DataMapperInterface
     */
    protected $dataMapper;

    /**
     * Constructor.
     *
     * @param \PDO $db
     * @param string $tableName
     * @param \Rybakit\TaskQueue\DataMapper\DataMapperInterface|null $dataMapper
     */
    public function __construct(PDO $db, $tableName, DataMapperInterface $dataMapper = null)
    {
        $this->db = $db;
        $this->tableName = (string) $tableName;
        $this->dataMapper = $dataMapper ?: new DataMapper();
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
     * Retrieves data mapper instance.
     *
     * @return \Rybakit\TaskQueue\DataMapper\DataMapperInterface
     */
    public function getDataMapper()
    {
        return $this->dataMapper;
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
        $sql = sprintf('INSERT INTO %s (%s) VALUES (:%s)',
            $this->tableName, implode(', ', $columnNames), implode(', :', $columnNames));

        $stmt = $this->db->prepare($sql);
        foreach ($data as $columnName => $value) {
            $stmt->bindValue(':'.$columnName, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        try {
            $this->db->beginTransaction();
            if (!$stmt->execute()) {
                $err = $stmt->errorInfo();
                throw new \RuntimeException($err[2]);
            }

            if (!$id = $this->db->lastInsertId()) {
                throw new \RuntimeException('Unable to retrieve the ID of the last inserted task.');
            }

            $this->db->commit();
        } catch(\Execption $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->dataMapper->inject($task, array('id' => $id));
    }

    /**
     * @see TaskQueueInterface::pop()
     */
    public function pop()
    {
        $sql = '
            SELECT id, payload, eta, max_retry_count, retry_delay, retry_count, _task_class
            FROM '.$this->tableName.'
            WHERE eta <= :now
            ORDER BY eta, id
            LIMIT 1';

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

    /**
     * @see TaskQueueInterface::peek()
     */
    public function peek($limit = 1, $skip = 0)
    {
        $sql = '
            SELECT id, payload, eta, max_retry_count, retry_delay, retry_count, _task_class
            FROM '.$this->tableName.'
            WHERE eta <= :now
            ORDER BY eta, id';

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
        $dataMapper = $this->dataMapper;

        return new IterableResult($stmt, function (array $data) use ($self, $dataMapper) {
            $data = $self->normalizeData($data, true);
            return $dataMapper->inject($data['_task_class'], $data);
        });
    }

    /**
     * @see TaskQueueInterface::remove()
     */
    /*
    public function remove()
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->tableName, implode(' AND ', $where));

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        if (!$result = $stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }

        return $result;
    }
    */

    /**
     * @param array $data
     * @param bool $invert
     *
     * @return array
     */
    public function normalizeData(array $data, $invert = false)
    {
        if ($invert) {
            $data['payload'] = unserialize(base64_decode($data['payload']));
            $data['eta'] = new \DateTime($data['eta']);
        } else {
            $data['payload'] = base64_encode(serialize($data['payload']));
            $data['eta'] = $data['eta'] ?: new \DateTime();
            $data['eta'] = $data['eta']->format(self::DATETIME_FORMAT);
        }

        return $data;
    }
}