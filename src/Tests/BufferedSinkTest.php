<?php

namespace Thruster\Component\Stream\Tests;

use Thruster\Component\Stream\BufferedSink;
use Thruster\Component\Stream\ReadableStream;

/**
 * Class BufferedSinkTest
 *
 * @package Thruster\Component\Stream\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class BufferedSinkTest extends TestCase
{
    public function testPromiseShouldReturnPromise()
    {
        $sink     = new BufferedSink();
        $contents = $sink->promise();

        $this->assertInstanceOf('Thruster\Component\Promise\PromiseInterface', $contents);
    }

    public function testEndShouldResolvePromiseWithBufferContents()
    {
        $callback = $this->expectCallableOnceWith('foo');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $sink->write('foo');
        $sink->end();
    }

    public function testCloseWithEmptyBufferShouldResolveToEmptyString()
    {
        $callback = $this->expectCallableOnceWith('');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $sink->close();
        $sink->close();
    }

    public function testCloseTwiceShouldBeFine()
    {
        $callback = $this->expectCallableOnce();

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $sink->close();
        $sink->close();
    }

    public function testResolvedValueShouldContainMultipleWrites()
    {
        $callback = $this->expectCallableOnceWith('foobarbaz');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $sink->write('foo');
        $sink->write('bar');
        $sink->write('baz');
        $sink->end();
    }

    public function testDataWrittenOnEndShouldBeBuffered()
    {
        $callback = $this->expectCallableOnceWith('foobar');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $sink->write('foo');
        $sink->end('bar');
    }

    public function testErrorsShouldRejectPromise()
    {
        $errback = $this->expectCallableOnceWith($this->callback(function ($e) {
            return $e instanceof \Exception && 'Shit happens' === $e->getMessage();
        }));

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($this->expectCallableNever(), $errback);

        $sink->emit('error', [new \Exception('Shit happens')]);
    }

    public function testWriteShouldNotTriggerProgressOnPromise()
    {
        $callback = $this->createCallableMock();
        $callback
            ->expects($this->never())
            ->method(TestCase::MOCK_FUNCTION);

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then(null, null, $this->getCallable($callback));

        $sink->write('foo');
    }

    public function testForwardedErrorsFromPipeShouldRejectPromise()
    {
        $errback = $this->expectCallableOnceWith($this->callback(function ($e) {
            return $e instanceof \Exception && 'Shit happens' === $e->getMessage();
        }));

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($this->expectCallableNever(), $errback);

        $readable = new ReadableStream();
        $readable->pipe($sink);
        $readable->emit('error', [new \Exception('Shit happens')]);
    }

    public function testPipeShouldSucceedAndResolve()
    {
        $callback = $this->expectCallableOnceWith('foobar');

        $sink = new BufferedSink();
        $sink
            ->promise()
            ->then($callback);

        $readable = new ReadableStream();
        $readable->pipe($sink);
        $readable->emit('data', ['foo']);
        $readable->emit('data', ['bar']);
        $readable->close();
    }

    public function testFactoryMethodShouldImplicitlyPipeAndPromise()
    {
        $callback = $this->expectCallableOnceWith('foo');

        $readable = new ReadableStream();

        BufferedSink::createPromise($readable)
            ->then($callback);

        $readable->emit('data', ['foo']);
        $readable->close();
    }

    private function expectCallableOnceWith($value)
    {
        $callback = $this->createCallableMock();
        $callback
            ->expects($this->once())
            ->method(TestCase::MOCK_FUNCTION)
            ->with($value);

        return $this->getCallable($callback);
    }
}
