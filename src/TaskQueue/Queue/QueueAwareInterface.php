<?php

namespace TaskQueue\Queue;

interface QueueAwareInterface
{
    function setQueue(QueueInterface $queue);
}