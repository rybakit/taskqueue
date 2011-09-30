<?php

namespace TaskQueue\Tests\Queue;

use TaskQueue\Queue\PhpQueue;
use TaskQueue\Task\Task;

class PhpQueueTest extends \PHPUnit_Framework_TestCase
{
    public function testPop()
    {
        $queue = new PhpQueue();

        $t1 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t1);

        $t2 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t2);

        $this->assertSame($t1, $queue->pop());
        $this->assertSame($t2, $queue->pop());
        $this->assertFalse($queue->pop());
    }

    public function testPopOrder()
    {
        $queue = new PhpQueue();

        $t1 = new Task(null, '+1 hour');
        $queue->push($t1);

        $t2 = new Task(null);
        $queue->push($t2);

        $this->assertSame($t2, $queue->pop());
        $this->assertSame($t1, $queue->pop());
    }

    public function testPeek()
    {
        $queue = new PhpQueue();

        $t1 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t1);

        $t2 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t2);

        $t3 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t3);

        $tasks = $queue->peek(2, 1);

        $this->assertInstanceOf('Iterator', $tasks);

        $tasks->rewind();
        $this->assertSame($t2, $tasks->current());
        $tasks->next();
        $this->assertSame($t3, $tasks->current());
        $tasks->next();
        $this->assertEmpty($tasks->current());
    }

    public function testPeekLimitRange()
    {
        $queue = new PhpQueue();

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
        $queue = new PhpQueue();

        try {
            $tasks = $queue->peek(1, -1);
            $this->fail('peek() throws an \OutOfRangeException if skip less then 0');
        } catch (\Exception $e) {
            $this->assertInstanceOf('OutOfRangeException', $e, 'peek() throws an \OutOfRangeException if skip less then 0');
            $this->assertEquals('Parameter skip must be greater than or equal 0.', $e->getMessage());
        }
    }

    public function testCount()
    {
        $queue = new PhpQueue();
        $this->assertEquals(0, $queue->count());

        for ($i = 0; $i < 10; $i++) {
            $queue->push($this->getMock('TaskQueue\\Task\\TaskInterface'));
        }
        $this->assertEquals(10, $queue->count());
    }

    public function testClear()
    {
        $queue = new PhpQueue();

        for ($i = 0; $i < 10; $i++) {
            $queue->push($this->getMock('TaskQueue\\Task\\TaskInterface'));
        }

        $queue->clear();
        $this->assertEquals(0, $queue->count());
    }
}