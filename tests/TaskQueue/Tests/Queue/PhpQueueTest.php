<?php

namespace TaskQueue\Tests\Task;

use TaskQueue\Queue\PhpQueue;
use TaskQueue\Task\Task;

class ArrayQueueTest extends \PHPUnit_Framework_TestCase
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

    public function testPeek()
    {
        $queue = new PhpQueue();

        $t1 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t1);

        $t2 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t2);

        $tasks = $queue->peek();
        $this->assertInstanceOf('Iterator', $tasks);
        $this->assertEquals(1, $tasks->count());
        //$this->assertEquals(2, $queue->size());
        $this->assertSame($t1, reset($tasks));
    }

    public function testPeekWithLimit()
    {
        $queue = new PhpQueue();

        $t1 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t1);

        $t2 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t2);

        $tasks = $queue->peek(2);
        $this->assertInstanceOf('Iterator', $tasks);
        $this->assertEquals(2, $tasks->count());
        $this->assertSame($t1, reset($tasks));
        $this->assertSame($t2, next($tasks));

        $tasks = $queue->peek(0);
        $this->assertInstanceOf('Iterator', $tasks);
        $this->assertEquals(0, $tasks->count());

        $tasks = $queue->peek(-1);
        $this->assertInstanceOf('Iterator', $tasks);
        $this->assertEquals(0, $tasks->count());
    }

    public function testPeekWithSkip()
    {
        $queue = new PhpQueue();

        $t1 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t1);

        $t2 = $this->getMock('TaskQueue\\Task\\TaskInterface');
        $queue->push($t2);

        $tasks = $queue->peek(10, -1);
        $this->assertInstanceOf('Iterator', $tasks);
        $this->assertEquals(2, $tasks->count());
        $this->assertSame($t1, reset($tasks));
        $this->assertSame($t2, next($tasks));

        $tasks = $queue->peek(1, 1);
        $this->assertInstanceOf('Iterator', $tasks);
        $this->assertEquals(1, $tasks->count());
        $this->assertSame($t2, reset($tasks));

        $tasks = $queue->peek(1, 2);
        $this->assertInstanceOf('Iterator', $tasks);
        $this->assertEquals(0, $tasks->count());
    }
}