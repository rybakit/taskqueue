<?php

namespace TaskQueue\Tests\Task;

use TaskQueue\Task\Task;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        try {
            $task = new Task();
            $this->fail('__construct() throws a warning and notice if payload is not specified');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\PHPUnit_Framework_Error_Warning', $e, '__construct() throws a warning and notice if the payload is not specified');
        }

        $payload = uniqid();
        $task = new Task($payload);
        $this->assertSame($payload, $task->getPayload());
        $this->assertEmpty($task->getEta());

        $eta = new \DateTime();
        $task = new Task(null, $eta);
        $this->assertSame($eta, $task->getEta());
    }

    public function testSetEta()
    {
        $eta = new \DateTime();
        $task = new Task(null, $eta);
        $this->assertSame($eta, $task->getEta());
    }

    public function testReschedule()
    {
        $eta = new \DateTime();

        $task = new Task(null, $eta);
        $task->setMaxRetryCount(2);

        $task->setRetryDelay(0);
        $this->assertTrue($task->reschedule());
        $this->assertEquals(1, $task->getRetryCount());
        $this->assertEquals($eta->getTimestamp(), $task->getEta()->getTimestamp());

        $task->setRetryDelay(5);
        $this->assertTrue($task->reschedule());
        $this->assertEquals(2, $task->getRetryCount());
        $this->assertEquals($eta->getTimestamp() + 5, $task->getEta()->getTimestamp());

        $this->assertFalse($task->reschedule());
        $this->assertEquals(2, $task->getRetryCount());
        $this->assertEquals($eta->getTimestamp() + 5, $task->getEta()->getTimestamp());
    }

    public function testClone()
    {
        $eta = new \DateTime();
        $task = new Task(null, $eta);

        $cloned = clone $task;
        $this->assertInstanceOf('DateTime', $cloned->getEta());
        $this->assertEquals($eta, $cloned->getEta());
        $task->getEta()->modify('+5 seconds');
        $this->assertNotEquals($task->getEta(), $cloned->getEta());

        $task = new Task(null);
        $cloned = clone $task;
        $this->assertNull($cloned->getEta());

        $task = new Task(null);
        $task->setMaxRetryCount(2);
        $task->reschedule();

        $cloned = clone $task;
        $this->assertEquals(1, $task->getRetryCount());
        $this->assertEquals(0, $cloned->getRetryCount());
    }
}