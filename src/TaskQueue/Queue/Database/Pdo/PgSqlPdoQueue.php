<?php

namespace TaskQueue\Queue\Database\Pdo;

class PgSqlPdoQueue extends PdoQueue
{
    public function __construct(\PDO $conn, $tableName)
    {
        if ('pgsql' != $conn->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            throw new \InvalidArgumentException('Invalid PDO driver specified.');
        }

        parent::__construct($conn, $tableName);
    }

    /**
     * @see QueueInterface::pop()
     * @see http://stackoverflow.com/questions/6507475/job-queue-as-sql-table-with-multiple-consumers-postgresql
     */
    public function pop()
    {
        $sql = 'SELECT id FROM '.$this->tableName.' WHERE eta <= :now ORDER BY eta, id LIMIT 1';
        $sql = 'DELETE FROM '.$this->tableName.' WHERE id = ('.$sql.') RETURNING task';

        $stmt = $this->prepareStatement($sql);
        $stmt->bindValue(':now', date(static::DATETIME_FORMAT));

        $this->conn->beginTransaction();
        $this->conn->exec('LOCK TABLE '.$this->tableName.' IN ACCESS EXCLUSIVE MODE');
        try {
            if (!$stmt->execute()) {
                $err = $stmt->errorInfo();
                throw new \RuntimeException($err[2]);
            }
            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }

        $result = $stmt->fetchColumn();

        return $result ? $this->normalizeData($result, true) : false;
    }
}