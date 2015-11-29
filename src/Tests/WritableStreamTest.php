<?php

namespace Thruster\Component\Stream\Tests;

use Thruster\Component\Stream\ReadableStream;
use Thruster\Component\Stream\WritableStream;

/**
 * Class WritableStreamTest
 *
 * @package Thruster\Component\Stream\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class WritableStreamTest extends TestCase
{
    public function testPipingStuffIntoItShouldWorkButDoNothing()
    {
        $readable = new ReadableStream();
        $through  = new WritableStream();

        $readable->pipe($through);
        $readable->emit('data', ['foo']);
    }

    public function testEndShouldCloseTheStream()
    {
        $through = new WritableStream();
        $through->on('data', $this->expectCallableNever());
        $through->end();

        $this->assertFalse($through->isWritable());
    }

    public function testEndShouldWriteDataBeforeClosing()
    {
        $through = $this->getMock('Thruster\Component\Stream\WritableStream', ['write']);
        $through
            ->expects($this->once())
            ->method('write')
            ->with('foo');
        $through->end('foo');

        $this->assertFalse($through->isWritable());
    }

    public function testItShouldBeWritableByDefault()
    {
        $through = new WritableStream();
        $this->assertTrue($through->isWritable());
    }

    public function testCloseShouldClose()
    {
        $through = new WritableStream();
        $through->close();

        $this->assertFalse($through->isWritable());
    }

    public function testDoubleCloseShouldWork()
    {
        $through = new WritableStream();
        $through->close();
        $through->close();

        $this->assertFalse($through->isWritable());
    }
}
