<?php

namespace Thruster\Component\Stream\Tests;

use Thruster\Component\Stream\Stream;

/**
 * Class StreamTest
 *
 * @package Thruster\Component\Stream\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class StreamTest extends TestCase
{
    public function testConstructor()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $conn = new Stream($stream, $loop);
    }

    public function testConstructorThrowsExceptionOnInvalidStream()
    {
        $this->setExpectedException('InvalidArgumentException');
        $loop = $this->createLoopMock();
        $conn = new Stream('breakme', $loop);
    }

    public function testDataEvent()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $capturedData = null;

        $conn = new Stream($stream, $loop);
        $conn->on('data', function ($data) use (&$capturedData) {
            $capturedData = $data;
        });

        fwrite($stream, "foobar\n");
        rewind($stream);

        $conn->handleData($stream);
        $this->assertSame("foobar\n", $capturedData);
    }

    public function testWrite()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createWriteableLoopMock();

        $conn = new Stream($stream, $loop);
        $conn->write("foo\n");

        rewind($stream);
        $this->assertSame("foo\n", fgets($stream));
    }

    public function testEnd()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $conn = new Stream($stream, $loop);
        $conn->end();

        $this->assertFalse(is_resource($stream));
    }

    public function testBufferEventsShouldBubbleUp()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $conn = new Stream($stream, $loop);

        $conn->on('drain', $this->expectCallableOnce());
        $conn->on('error', $this->expectCallableOnce());

        $buffer = $conn->getBuffer();
        $buffer->emit('drain');
        $buffer->emit('error', array(new \RuntimeException('Whoops')));
    }

    public function testClosingStreamInDataEventShouldNotTriggerError()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $conn = new Stream($stream, $loop);
        $conn->on('data', function ($data, $stream) {
            $stream->close();
        });

        fwrite($stream, "foobar\n");
        rewind($stream);

        $conn->handleData($stream);
    }

    protected function createWriteableLoopMock()
    {
        $loop = $this->createLoopMock();
        $loop
            ->expects($this->once())
            ->method('addWriteStream')
            ->will($this->returnCallback(function ($stream, $listener) {
                call_user_func($listener, $stream);
            }));

        return $loop;
    }

    protected function createLoopMock()
    {
        return $this->getMock('Thruster\Component\EventLoop\EventLoopInterface');
    }
}
