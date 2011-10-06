<?php

namespace TaskQueue\Tests\Queue;

use TaskQueue\Task\Task;

abstract class AbstractQueueTest extends \PHPUnit_Framework_TestCase
{
    public function testPop()
    {
        $queue = $this->createQueue();

        $t1 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t1);

        $t2 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t2);

        $this->assertEquals($t1, $queue->pop());
        $this->assertEquals($t2, $queue->pop());
        $this->assertFalse($queue->pop());
    }

    public function testPopOrder()
    {
        $queue = $this->createQueue();

        $t1 = new Task(null, '+2 seconds');
        $queue->push($t1);

        $t2 = new Task(null);
        $queue->push($t2);

        $t3 = new Task(null, '+1 hour');
        $queue->push($t3);

        $this->assertEquals($t2, $queue->pop());
        sleep(2);
        $this->assertEquals($t1, $queue->pop());
        $this->assertFalse($queue->pop());
    }

    public function testPeek()
    {
        $queue = $this->createQueue();

        $t1 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t1);

        $t2 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t2);

        $t3 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t3);

        $tasks = $queue->peek(2, 1);

        $this->assertInstanceOf('Iterator', $tasks);

        $tasks->rewind();
        $this->assertEquals($t2, $tasks->current());
        $tasks->next();
        $this->assertEquals($t3, $tasks->current());
        $tasks->next();
        $this->assertEmpty($tasks->current());
    }

    public function testPeekLimitRange()
    {
        $queue = $this->createQueue();

        try {
            $tasks = $queue->peek(0);
            $this->fail('peek() throws an \OutOfRangeException if limit less then or equal 0');
        } catch (\Exception $e) {
            $this->assertInstanceOf('OutOfRangeException', $e, 'peek() throws an \OutOfRangeException if limit less then or equal 0');
            $this->assertEquals('Parameter limit must be greater than 0.', $e->getMessage());
        }
    }

    public function testPeekSkipRange()
    {
        $queue = $this->createQueue();

        try {
            $tasks = $queue->peek(1, -1);
            $this->fail('peek() throws an \OutOfRangeException if skip less then 0');
        } catch (\Exception $e) {
            $this->assertInstanceOf('OutOfRangeException', $e, 'peek() throws an \OutOfRangeException if skip less then 0');
            $this->assertEquals('Parameter skip must be greater than or equal 0.', $e->getMessage());
        }
    }

    public function testCountAndClear()
    {
        $queue = $this->createQueue();
        $this->assertEquals(0, $queue->count());

        for ($i = 0; $i < 7; $i++) {
            $queue->push($this->getMock('TaskQueue\\Task\\TaskInterface'));
        }
        $this->assertEquals(7, $queue->count());

        $queue->clear();
        $this->assertEquals(0, $queue->count());
    }

    abstract protected function createQueue();
}