<?php

namespace Thruster\Component\Stream\Tests;

use Thruster\Component\Stream\ReadableStream;
use Thruster\Component\Stream\ThroughStream;

/**
 * Class ThroughStreamTest
 *
 * @package Thruster\Component\Stream\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class ThroughStreamTest extends TestCase
{
    public function testItShouldEmitAnyDataWrittenToIt()
    {
        $through = new ThroughStream();
        $through->on('data', $this->expectCallableOnceWith('foo'));
        $through->write('foo');
    }

    public function testPipingStuffIntoItShouldWork()
    {
        $readable = new ReadableStream();

        $through = new ThroughStream();
        $through->on('data', $this->expectCallableOnceWith('foo'));

        $readable->pipe($through);
        $readable->emit('data', ['foo']);
    }

    public function testEndShouldCloseTheStream()
    {
        $through = new ThroughStream();
        $through->on('data', $this->expectCallableNever());
        $through->end();

        $this->assertFalse($through->isReadable());
        $this->assertFalse($through->isWritable());
    }

    public function testEndShouldWriteDataBeforeClosing()
    {
        $through = new ThroughStream();
        $through->on('data', $this->expectCallableOnceWith('foo'));
        $through->end('foo');

        $this->assertFalse($through->isReadable());
        $this->assertFalse($through->isWritable());
    }

    public function testItShouldBeReadableByDefault()
    {
        $through = new ThroughStream();
        $this->assertTrue($through->isReadable());
    }

    public function testItShouldBeWritableByDefault()
    {
        $through = new ThroughStream();
        $this->assertTrue($through->isWritable());
    }

    public function testpauseShouldDelegateToPipeSource()
    {
        $input = $this->getMock('Thruster\Component\Stream\ReadableStream', ['pause']);
        $input
            ->expects($this->once())
            ->method('pause');

        $through = new ThroughStream();
        $input->pipe($through);

        $through->pause();
    }

    public function testResumeShouldDelegateToPipeSource()
    {
        $input = $this->getMock('Thruster\Component\Stream\ReadableStream', ['resume']);
        $input
            ->expects($this->once())
            ->method('resume');

        $through = new ThroughStream();
        $input->pipe($through);

        $through->resume();
    }

    public function testCloseShouldClose()
    {
        $through = new ThroughStream();
        $through->close();

        $this->assertFalse($through->isReadable());
        $this->assertFalse($through->isWritable());
    }

    public function testDoubleCloseShouldWork()
    {
        $through = new ThroughStream();
        $through->close();
        $through->close();

        $this->assertFalse($through->isReadable());
        $this->assertFalse($through->isWritable());
    }

    public function testPipeShouldPipeCorrectly()
    {
        $output = $this->getMock('Thruster\Component\Stream\WritableStreamInterface');
        $output
            ->expects($this->once())
            ->method('write')
            ->with('foo');

        $through = new ThroughStream();
        $through->pipe($output);
        $through->write('foo');
    }

    protected function expectCallableOnceWith($arg)
    {
        $mock = $this->createCallableMock();

        $mock->expects($this->once())
            ->method(TestCase::MOCK_FUNCTION);

        return $this->getCallable($mock);
    }
}
