<?php

namespace TaskQueue\DataMapper;

interface InjectorInterface
{
    /**
     * @param array $data
     *
     * @return object
     */
    function inject(array $data);
}