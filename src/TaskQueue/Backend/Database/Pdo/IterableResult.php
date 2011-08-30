<?php

namespace Rybakit\TaskQueue\Backend\Database\Pdo;

class IterableResult implements \Iterator
{
    /**
     * The PHP PDOStatement instance.
     *
     * @var \PDOStatement
     */
    protected $stmt;

    /**
     * A PHP callback to transform result set into another structure (e.g. object).
     *
     * @var \Closure|string|array
     */
    protected $hydrator;

    /**
     * @var boolean
     */
    private $rewinded = false;

    /**
     * @var integer
     */
    private $key = -1;

    /**
     * @var object
     */
    private $current = null;

    /**
     * Constructor.
     *
     * @param \PDOStatement $stmt
     * @param \Closure|string|array $hydrator
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\PDOStatement $stmt, $hydrator)
    {
        if (!is_callable($hydrator)) {
            throw new \InvalidArgumentException('The given hydrator is not a valid callable.');
        }

        $this->stmt = $stmt;
        $this->hydrator = $hydrator;
    }

    public function current()
    {
        return $this->current;
    }

    public function key()
    {
        return $this->key;
    }

    public function rewind()
    {
        if ($this->rewinded) {
            throw new \LogicException('Can only iterate a Result once.');
        } else {
            $this->current = $this->next();
            $this->rewinded = true;
        }
    }

    public function next()
    {
        $data = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        $this->current = is_array($data) ? call_user_func($this->hydrator, $data) : false;
        $this->key++;

        return $this->current;
    }

    public function valid()
    {
        return false != $this->current;
    }
}
