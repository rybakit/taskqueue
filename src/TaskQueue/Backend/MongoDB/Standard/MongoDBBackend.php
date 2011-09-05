<?php

namespace TaskQueue\Backend\MongoDB\Standard;

use TaskQueue\TaskQueueInterface;
use TaskQueue\Task\TaskInterface;

class MongoDBBackend implements TaskQueueInterface
{
    /**
     * @var \MongoCollection
     */
    protected $collection;

    /**
     * Constructor.
     *
     * @param \MongoCollection $collection
     */
    public function __construct(\MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Retrieves \MongoCollection instance.
     *
     * @return \MongoCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @see TaskQueueInterface::push()
     */
    public function push(TaskInterface $task)
    {
        $eta = $task->getEta() ?: new \DateTime();

        $data = array(
            'eta'   => new \MongoDate($eta->getTimestamp()),
            'task'  => $this->normalizeData($task),
        );

        $this->collection->insert($data, array('safe' => true));
    }

    /**
     * @see TaskQueueInterface::pop()
     */
    public function pop()
    {
        $command = array(
            'findandmodify' => $this->collection->getName(),
            'remove'        => true,
            'fields'        => array('task' => 1),
            'query'         => array('eta' => array('$lte' => new \MongoDate())),
            'sort'          => array('eta' => 1),
        );

        $result = $this->collection->db->command($command);
        if (!isset($result['ok']) || !$result['ok']) {
            throw new \RuntimeException(isset($result['errmsg']) ? $result['errmsg'] : 'Unable to query collection.');
        }

        $data = $result['value'];

        return $data ? $this->normalizeData($data['task'], true) : false;
    }

    /**
     * @see TaskQueueInterface::peek()
     */
    public function peek($limit = 1, $skip = 0)
    {
        $cursor = $this->collection->find(array('eta' => array('$lte' => new \MongoDate())));
        $cursor->sort(array('eta' => 1));

        if ($limit) {
            $cursor->limit($limit);
        }

        if ($skip) {
            $cursor->skip($skip);
        }

        $self = $this;
        return new IterableResult($cursor, function ($data) use ($self) {
            return $self->normalizeData($data, true);
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