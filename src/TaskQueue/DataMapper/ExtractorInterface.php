<?php

namespace TaskQueue\DataMapper;

interface ExtractorInterface
{
    /**
     * @return array
     */
    function extract();
}