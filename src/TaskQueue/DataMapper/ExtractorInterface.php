<?php

namespace Rybakit\TaskQueue\DataMapper;

interface ExtractorInterface
{
    /**
     * @return array
     */
    function extract();
}