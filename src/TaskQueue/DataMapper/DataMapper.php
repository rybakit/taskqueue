<?php

namespace Rybakit\TaskQueue\DataMapper;

class DataMapper implements DataMapperInterface
{
    /**
     * An array of prototypes from which new instances of the class are created.
     *
     * @var array
     */
    protected static $prototypes = array();

    /**
     * @see DataMapperInterface::extract()
     */
    public function extract($object)
    {
        if (!$object instanceof ExtractorInterface) {
            throw new \InvalidArgumentException(sprintf('Class "%s" must implement ExtractorInterface.', get_class($object)));
        }

        return $object->extract();
    }

    /**
     * @see DataMapperInterface::inject()
     */
    public function inject($object, array $data)
    {
        if (!is_object($object)) {
            $object = $this->createObject($object);
        }

        if (!$object instanceof InjectorInterface) {
            throw new \InvalidArgumentException(sprintf('Class "%s" must implement InjectorInterface.', get_class($object)));
        }

        $object->inject($data);

        return $object;
    }

    /**
     * Creates a new instance of the given class, without invoking the constructor.
     *
     * @param string $class
     *
     * @throws \InvalidArgumentException
     *
     * @return object
     */
    protected function createObject($class)
    {
        if (!is_string($class)) {
            throw new \InvalidArgumentException('$class argument must be a string.');
        }

        if (!isset(self::$prototypes[$class])) {
            self::$prototypes[$class] = unserialize(sprintf('O:%d:"%s":0:{}', strlen($class), $class));
        }

        return clone self::$prototypes[$class];
    }
}