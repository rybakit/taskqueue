<?php

namespace TaskQueue\Queue\Database\Pdo;

class PostgresPdoQueue extends PdoQueue
{
    public function __construct(\PDO $db, $tableName)
    {
        if ('pgsql' != $db->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            throw new \InvalidArgumentException('Invalid PDO driver specified.');
        }

        parent::__construct($db, $tableName);
    }

    /**
     * @see QueueInterface::pop()
     */
    public function pop()
    {
        $sql = 'SELECT id FROM '.$this->tableName.' WHERE eta <= :now ORDER BY eta, id LIMIT 1';
        $sql = 'DELETE FROM '.$this->tableName.' WHERE id = ('.$sql.') RETURNING task';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':now', date(self::DATETIME_FORMAT));

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \RuntimeException($err[2]);
        }

        $result = $stmt->fetchColumn();

        return $result ? $this->normalizeData($result, true) : false;
    }
}