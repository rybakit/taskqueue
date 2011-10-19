<?php

namespace TaskQueue\Tests\Queue;

abstract class AbstractQueueTest extends \PHPUnit_Framework_TestCase
{
    public function testPushPop()
    {
        $task = $this->createTaskMock();
        $queue = $this->createQueue();

        $this->assertFalse($queue->pop());
        $queue->push($task);
        $this->assertEquals($task, $queue->pop());
        $this->assertFalse($queue->pop());
    }

    public function testPushPopOrder()
    {
        $t1 = $this->createTaskMock();
        $t1->expects($this->atLeastOnce())
            ->method('getEta')
            ->will($this->returnValue(new \DateTime('+2 seconds')));

        $t2 = $this->createTaskMock();
        $t2->expects($this->atLeastOnce())
            ->method('getEta')
            ->will($this->returnValue(new \DateTime()));

        $queue = $this->createQueue();
        $queue->push($t1);
        $queue->push($t2);

        $this->assertEquals($t2, $queue->pop());
        $this->assertFalse($queue->pop());
        sleep(2);
        $this->assertEquals($t1, $queue->pop());
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
        $mock = $this->getMockBuilder('TaskQueue\Task\TaskInterface')
            ->setMockClassName('Mock_TaskInterface_'.uniqid())
            ->getMock();

        // need at least one method for proper
        // serialization/deserialization of mocked object, e.g.:
        // $this->assertEquals($mock, unserialize(serialize($mock)));
        $mock->expects($this->any())
            ->method('fakeMethod')
            ->will($this->returnValue(true));

        return $mock;
    }

    /**
     * @return \TaskQueue\Queue\QueueInterface
     */
    abstract protected function createQueue();
}