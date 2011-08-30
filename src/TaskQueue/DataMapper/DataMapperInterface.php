<?php

namespace TaskQueue\DataMapper;

interface DataMapperInterface
{
    /**
     * Extracts object data to array.
     *
     * @param object $object
     *
     * @return array
     */
    function extract($object);

    /**
     * Injects data to object.
     *
     * @param object|string $object
     * @param array $data
     *
     * @return object
     */
    function inject($object, array $data);
}