<?php

namespace TaskQueue\Worker\Strategy;

interface StrategyInterface
{
    function getNext();
}