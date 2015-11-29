<?php

namespace Thruster\Component\Stream\Tests;

use Thruster\Component\Stream\CompositeStream;
use Thruster\Component\Stream\ReadableStream;
use Thruster\Component\Stream\WritableStream;

/**
 * Class CompositeStreamTest
 *
 * @package Thruster\Component\Stream\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class CompositeStreamTest extends TestCase
{
    public function testItShouldForwardWritableCallsToWritableStream()
    {
        $readable = $this->getMock('Thruster\Component\Stream\ReadableStreamInterface');
        $writable = $this->getMock('Thruster\Component\Stream\WritableStreamInterface');
        $writable
            ->expects($this->once())
            ->method('write')
            ->with('foo');
        $writable
            ->expects($this->once())
            ->method('isWritable');

        $composite = new CompositeStream($readable, $writable);
        $composite->write('foo');
        $composite->isWritable();
    }

    public function testItShouldForwardReadableCallsToReadableStream()
    {
        $readable = $this->getMock('Thruster\Component\Stream\ReadableStreamInterface');
        $readable
            ->expects($this->once())
            ->method('isReadable');
        $readable
            ->expects($this->once())
            ->method('pause');
        $readable
            ->expects($this->once())
            ->method('resume');
        $writable = $this->getMock('Thruster\Component\Stream\WritableStreamInterface');

        $composite = new CompositeStream($readable, $writable);
        $composite->isReadable();
        $composite->pause();
        $composite->resume();
    }

    public function testEndShouldDelegateToWritableWithData()
    {
        $readable = $this->getMock('Thruster\Component\Stream\ReadableStreamInterface');
        $writable = $this->getMock('Thruster\Component\Stream\WritableStreamInterface');
        $writable
            ->expects($this->once())
            ->method('end')
            ->with('foo');

        $composite = new CompositeStream($readable, $writable);
        $composite->end('foo');
    }

    public function testCloseShouldCloseBothStreams()
    {
        $readable = $this->getMock('Thruster\Component\Stream\ReadableStreamInterface');
        $readable
            ->expects($this->once())
            ->method('close');
        $writable = $this->getMock('Thruster\Component\Stream\WritableStreamInterface');
        $writable
            ->expects($this->once())
            ->method('close');

        $composite = new CompositeStream($readable, $writable);
        $composite->close();
    }

    public function testItShouldReceiveForwardedEvents()
    {
        $readable = new ReadableStream();
        $writable = new WritableStream();

        $composite = new CompositeStream($readable, $writable);
        $composite->on('data', $this->expectCallableOnce());
        $composite->on('drain', $this->expectCallableOnce());

        $readable->emit('data', array('foo'));
        $writable->emit('drain');
    }

    public function testItShouldHandlePipingCorrectly()
    {
        $readable = $this->getMock('Thruster\Component\Stream\ReadableStreamInterface');
        $writable = $this->getMock('Thruster\Component\Stream\WritableStreamInterface');
        $writable
            ->expects($this->once())
            ->method('write')
            ->with('foo');

        $composite = new CompositeStream($readable, $writable);

        $input = new ReadableStream();
        $input->pipe($composite);
        $input->emit('data', array('foo'));
    }

    public function testItShouldForwardPauseAndResumeUpstreamWhenPipedTo()
    {
        $readable = $this->getMock('Thruster\Component\Stream\ReadableStreamInterface');
        $writable = $this->getMock('Thruster\Component\Stream\WritableStream', array('write'));
        $writable
            ->expects($this->once())
            ->method('write')
            ->will($this->returnValue(false));

        $composite = new CompositeStream($readable, $writable);

        $input = $this->getMock('Thruster\Component\Stream\ReadableStream', array('pause', 'resume'));
        $input
            ->expects($this->once())
            ->method('pause');
        $input
            ->expects($this->once())
            ->method('resume');

        $input->pipe($composite);
        $input->emit('data', array('foo'));
        $writable->emit('drain');
    }

    public function testItShouldForwardPipeCallsToReadableStream()
    {
        $readable = new ReadableStream();
        $writable = $this->getMock('Thruster\Component\Stream\WritableStreamInterface');
        $composite = new CompositeStream($readable, $writable);

        $output = $this->getMock('Thruster\Component\Stream\WritableStreamInterface');
        $output
            ->expects($this->once())
            ->method('write')
            ->with('foo');

        $composite->pipe($output);
        $readable->emit('data', array('foo'));
    }
}
