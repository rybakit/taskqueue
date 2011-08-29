<?php

namespace Rybakit\TaskQueue\Backend\MongoDB\Standard;

use Rybakit\TaskQueue\TaskQueueInterface;
use Rybakit\TaskQueue\DataMapper\DataMapperInterface;
use Rybakit\TaskQueue\DataMapper\DataMapper;
use Rybakit\TaskQueue\Task\Task;
use Rybakit\TaskQueue\Task\TaskInterface;

class MongoDBBackend implements TaskQueueInterface
{
    /**
     * @var \MongoCollection
     */
    protected $collection;

    /**
     * @var \Rybakit\TaskQueue\DataMapper\DataMapperInterface
     */
    protected $dataMapper;

    /**
     * Constructor.
     *
     * @param \MongoCollection $collection
     * @param \Rybakit\TaskQueue\DataMapper\DataMapperInterface|null $dataMapper
     */
    public function __construct(\MongoCollection $collection, DataMapperInterface $dataMapper = null)
    {
        $this->collection = $collection;
        $this->dataMapper = $dataMapper ?: new DataMapper();
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

        // TODO add check for error
        $this->collection->insert($data);

        $this->dataMapper->inject($task, array('id' => $data['_id']));
    }

    /**
     * @see TaskQueueInterface::pop()
     */
    public function pop(array $taskNames = array())
    {
        $query = array('$or' => array(array('eta' => array('$lte' => new \MongoDate())), array('eta' => null)));
        if ($taskNames) {
            $query = array('$and' => array($query, array('name' => array('$in' => $taskNames))));
        }

        $command = array(
            'findandmodify' => $this->collection->getName(),
            'remove'        => true,
            'query'         => $query,
            'sort'          => array('eta' => 1),
        );

        $result = $this->collection->db->command($command);
        if (!isset($result['ok']) || !$result['ok']) {
            throw new \RuntimeException(isset($result['errmsg']) ? $result['errmsg'] : 'Unable to query collection.');
        }

        if (!$data = $result['value']) {
            return false;
        }

        $data = $this->normalizeData($data, true);

        return $this->dataMapper->inject($data['_task_class'], $data);
    }

    /**
     * @see TaskQueueInterface::peek()
     */
    public function peek(array $taskNames = array(), $limit = 1, $skip = 0)
    {
        $query = array('$or' => array(array('eta' => array('$lte' => new \MongoDate())), array('eta' => null)));
        if ($taskNames) {
            $query = array('$and' => array($query, array('name' => array('$in' => $taskNames))));
        }

        // TODO add check for error
        $cursor = $this->collection->find($query);

        $cursor->sort(array('eta' => 1));

        if ($limit) {
            $cursor->limit($limit);
        }

        if ($skip) {
            $cursor->skip($skip);
        }

        $self = $this;
        $dataMapper = $this->dataMapper;
        return new IterableResult($cursor, function (array $data) use ($self, $dataMapper) {
            $data = $self->normalizeData($data, true);
            return $dataMapper->inject($data['_task_class'], $data);
        });
    }

    /**
     * @see TaskQueueInterface::remove()
     */
    /*
    public function remove($task)
    {
        $result = $this->collection->remove($query, array('safe' => true));
        if (!isset($result['ok']) || !$result['ok']) {
            throw new \RuntimeException(isset($result['errmsg']) ? $result['errmsg'] : 'Unable to remove items.');
        }

        return $result['n'];
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
            $data['id'] = $data['_id'];
            $data['payload'] = unserialize(base64_decode($data['payload']));
            $date = new \DateTime();
            $data['eta'] = $date->setTimestamp($data['eta']->sec);
        } else {
            $data['payload'] = base64_encode(serialize($data['payload']));
            $data['eta'] = $data['eta'] ?: new \DateTime();
            $data['eta'] = new \MongoDate($data['eta']->getTimestamp());
        }

        return $data;
    }
}