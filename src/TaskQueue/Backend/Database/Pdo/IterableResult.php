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
     * A PHP callback to convert data to object.
     *
     * @var \Closure|string|array
     */
    protected $converter;

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
     * @param \PDOStatement $mongoCursor
     * @param \Closure|string|array $converter
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\PDOStatement $stmt, $converter)
    {
        if (!is_callable($converter)) {
            throw new \InvalidArgumentException('The given converter callback is not a valid callable.');
        }

        $this->stmt = $stmt;
        $this->converter = $converter;
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
        $this->current = is_array($data) ? call_user_func($this->converter, $data) : false;
        $this->key++;

        return $this->current;
    }

    public function valid()
    {
        return false != $this->current;
    }
}
