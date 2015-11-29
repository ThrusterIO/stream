<?php

namespace Thruster\Component\Stream\Tests;

use Thruster\Component\Stream\ReadableStream;

/**
 * Class ReadableStreamTest
 *
 * @package Thruster\Component\Stream\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class ReadableStreamTest extends TestCase
{
    public function testItShouldBeReadableByDefault()
    {
        $readable = new ReadableStream();
        $this->assertTrue($readable->isReadable());
    }

    public function testPauseShouldDoNothing()
    {
        $readable = new ReadableStream();
        $readable->pause();
    }

    public function testResumeShouldDoNothing()
    {
        $readable = new ReadableStream();
        $readable->resume();
    }

    public function testCloseShouldClose()
    {
        $readable = new ReadableStream();
        $readable->close();

        $this->assertFalse($readable->isReadable());
    }

    public function testDoubleCloseShouldWork()
    {
        $readable = new ReadableStream();
        $readable->close();
        $readable->close();

        $this->assertFalse($readable->isReadable());
    }
}
