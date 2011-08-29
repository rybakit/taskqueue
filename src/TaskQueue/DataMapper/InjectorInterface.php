<?php

namespace Rybakit\TaskQueue\DataMapper;

interface InjectorInterface
{
    /**
     * @param array $data
     *
     * @return object
     */
    function inject(array $data);
}