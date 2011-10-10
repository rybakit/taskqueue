<?php

namespace TaskQueue\Tests\Queue;

abstract class AbstractQueueTest extends \PHPUnit_Framework_TestCase
{
    public function testPushPop()
    {
        $t1 = $this->createTaskMock();
        $t1->expects($this->atLeastOnce())
            ->method('getEta')
            ->will($this->returnValue(new \DateTime('+1 seconds')));

        $t2 = $this->createTaskMock();
        $t2->expects($this->atLeastOnce())
            ->method('getEta')
            ->will($this->returnValue(new \DateTime()));

        $t3 = $this->createTaskMock();
        $t3->expects($this->atLeastOnce())
            ->method('getEta')
            ->will($this->returnValue(new \DateTime('+1 hour')));

        $queue = $this->createQueue();
        $queue->push($t1);
        $queue->push($t2);
        $queue->push($t3);

        $this->assertEquals($t2, $queue->pop());
        $this->assertFalse($queue->pop());
        sleep(1);
        $this->assertEquals($t1, $queue->pop());
        $this->assertFalse($queue->pop());
    }

    public function testPeek()
    {
        $t1 = $this->createTaskMock();
        $t2 = $this->createTaskMock();
        $t3 = $this->createTaskMock();

        $queue = $this->createQueue();
        $queue->push($t1);
        $queue->push($t2);
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

        $task = $this->createTaskMock();
        for ($i = 0; $i < 7; $i++) {
            $queue->push($task);
        }
        $this->assertEquals(7, $queue->count());

        $queue->clear();
        $this->assertEquals(0, $queue->count());
    }

    protected function createTaskMock()
    {
        return $this->getMockBuilder('TaskQueue\Task\TaskInterface')
            ->setMockClassName('Mock_TaskInterface_'.uniqid())
            ->getMock();
    }

    abstract protected function createQueue();
}