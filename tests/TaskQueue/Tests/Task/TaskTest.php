<?php

namespace TaskQueue\Tests\Task;

use TaskQueue\Task\Task;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    public function testReschedule()
    {
        $eta = new \DateTime();
        $retryDelaySec = 2;

        $task = new Task(null, $eta);
        $task->setMaxRetryCount(1);
        $task->setRetryDelay($retryDelaySec);

        $this->assertTrue($task->reschedule());
        $this->assertEquals(1, $task->getRetryCount());
        $this->assertEquals($eta->getTimestamp() + $retryDelaySec, $task->getEta()->getTimestamp());

        $this->assertFalse($task->reschedule());
        $this->assertEquals(1, $task->getRetryCount());
        $this->assertEquals($eta->getTimestamp() + $retryDelaySec, $task->getEta()->getTimestamp());
    }
}