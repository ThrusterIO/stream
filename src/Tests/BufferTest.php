<?php

namespace Thruster\Component\Stream\Tests;

use Thruster\Component\Stream\Buffer;

/**
 * Class BufferTest
 *
 * @package Thruster\Component\Stream\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class BufferTest extends TestCase
{
    public function testConstructor()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $buffer = new Buffer($stream, $loop);
        $buffer->on('error', $this->expectCallableNever());
    }

    public function testWrite()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createWriteableLoopMock();

        $buffer = new Buffer($stream, $loop);
        $buffer->on('error', $this->expectCallableNever());

        $buffer->write("foobar\n");
        rewind($stream);
        $this->assertSame("foobar\n", fread($stream, 1024));
    }

    public function testWriteReturnsFalseWhenBufferIsFull()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createWriteableLoopMock();
        $loop->preventWrites = true;

        $buffer = new Buffer($stream, $loop);
        $buffer->setSoftLimit(4);
        $buffer->on('error', $this->expectCallableNever());

        $this->assertTrue($buffer->write("foo"));
        $loop->preventWrites = false;
        $this->assertFalse($buffer->write("bar\n"));
    }

    public function testWriteDetectsWhenOtherSideIsClosed()
    {
        list($a, $b) = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $loop = $this->createWriteableLoopMock();

        $buffer = new Buffer($a, $loop);
        $buffer->setSoftLimit(4);
        $buffer->on('error', $this->expectCallableOnce());

        fclose($b);

        $buffer->write("foo");
    }

    public function testDrain()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createWriteableLoopMock();
        $loop->preventWrites = true;

        $buffer = new Buffer($stream, $loop);
        $buffer->setSoftLimit(4);
        $buffer->on('error', $this->expectCallableNever());
        $buffer->on('drain', $this->expectCallableOnce());

        $buffer->write("foo");
        $loop->preventWrites = false;
        $buffer->setListening(false);
        $buffer->write("bar\n");
    }

    public function testWriteInDrain()
    {
        $writeStreams = array();

        $stream = fopen('php://temp', 'r+');
        $loop = $this->createWriteableLoopMock();
        $loop->preventWrites = true;

        $buffer = new Buffer($stream, $loop);
        $buffer->setSoftLimit(2);
        $buffer->on('error', $this->expectCallableNever());

        $buffer->once('drain', function ($buffer) {
            $buffer->setListening(false);
            $buffer->write("bar\n");
        });

        $this->assertFalse($buffer->write("foo"));
        $loop->preventWrites = false;
        $buffer->setListening(false);
        $buffer->write("\n");

        fseek($stream, 0);
        $this->assertSame("foo\nbar\n", stream_get_contents($stream));
    }

    public function testEnd()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $buffer = new Buffer($stream, $loop);
        $buffer->on('error', $this->expectCallableNever());
        $buffer->on('close', $this->expectCallableOnce());

        $this->assertTrue($buffer->isWritable());
        $buffer->end();
        $this->assertFalse($buffer->isWritable());
    }

    public function testEndWithData()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createWriteableLoopMock();

        $buffer = new Buffer($stream, $loop);
        $buffer->on('error', $this->expectCallableNever());
        $buffer->on('close', $this->expectCallableOnce());

        $buffer->end('final words');

        rewind($stream);
        $this->assertSame('final words', stream_get_contents($stream));
    }

    public function testClose()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $buffer = new Buffer($stream, $loop);
        $buffer->on('error', $this->expectCallableNever());
        $buffer->on('close', $this->expectCallableOnce());

        $this->assertTrue($buffer->isWritable());
        $buffer->close();
        $this->assertFalse($buffer->isWritable());
    }

    public function testWritingToClosedBufferShouldNotWriteToStream()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createWriteableLoopMock();

        $buffer = new Buffer($stream, $loop);
        $buffer->close();

        $buffer->write('foo');

        rewind($stream);
        $this->assertSame('', stream_get_contents($stream));
    }

    public function testError()
    {
        $stream = null;
        $loop = $this->createWriteableLoopMock();

        $error = null;

        $buffer = new Buffer($stream, $loop);
        $buffer->on('error', function ($message) use (&$error) {
            $error = $message;
        });

        $buffer->write('Attempting to write to bad stream');
        $this->assertInstanceOf('Exception', $error);
        $this->assertSame('Tried to write to invalid stream.', $error->getMessage());
    }

    public function testWritingToClosedStream()
    {
        if ('Darwin' === PHP_OS) {
            $this->markTestSkipped('OS X issue with shutting down pair for writing');
        }

        list($a, $b) = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $loop = $this->createWriteableLoopMock();

        $error = null;

        $buffer = new Buffer($a, $loop);
        $buffer->on('error', function($message) use (&$error) {
            $error = $message;
        });

        $buffer->write('foo');
        stream_socket_shutdown($b, STREAM_SHUT_RD);
        stream_socket_shutdown($a, STREAM_SHUT_RD);
        $buffer->write('bar');

        $this->assertInstanceOf('Exception', $error);
        $this->assertSame('Tried to write to closed stream.', $error->getMessage());
    }

    protected function createWriteableLoopMock()
    {
        $loop = $this->createLoopMock();
        $loop->preventWrites = false;
        $loop
            ->expects($this->any())
            ->method('addWriteStream')
            ->will($this->returnCallback(function ($stream, $listener) use ($loop) {
                if (!$loop->preventWrites) {
                    call_user_func($listener, $stream);
                }
            }));

        return $loop;
    }

    protected function createLoopMock()
    {
        return $this->getMock('Thruster\Component\EventLoop\EventLoopInterface');
    }
}
