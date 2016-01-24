<?php

namespace Thruster\Component\Stream;

use Thruster\Component\Promise\Deferred;
use Thruster\Component\Promise\PromiseInterface;
use Thruster\Component\Promise\PromisorInterface;

/**
 * Class BufferedSink
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class BufferedSink extends WritableStream implements PromisorInterface
{
    use UtilsTrait;

    private $buffer = '';
    private $deferred;

    public function __construct()
    {
        $this->deferred = new Deferred();

        $this->on('pipe', [$this, 'handlePipeEvent']);
        $this->on('error', [$this, 'handleErrorEvent']);

        parent::__construct();
    }

    public function handlePipeEvent($source)
    {
        $this->forwardEvents($source, $this, ['error']);
    }

    public function handleErrorEvent($e)
    {
        $this->deferred->reject($e);
    }

    public function write($data)
    {
        $this->buffer .= $data;
    }

    public function close()
    {
        if (true === $this->closed) {
            return;
        }

        parent::close();

        $this->deferred->resolve($this->buffer);
    }

    public function promise() : PromiseInterface
    {
        return $this->deferred->promise();
    }

    public static function createPromise(ReadableStreamInterface $stream) : PromiseInterface
    {
        $sink = new static();

        $stream->pipe($sink);

        return $sink->promise();
    }
}
