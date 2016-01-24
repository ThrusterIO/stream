<?php

namespace Thruster\Component\Stream;

use InvalidArgumentException;
use Thruster\Component\EventEmitter\EventEmitterInterface;
use Thruster\Component\EventEmitter\EventEmitterTrait;
use Thruster\Component\EventLoop\EventLoopInterface;

/**
 * Class Stream
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Stream implements DuplexStreamInterface
{
    use EventEmitterTrait;
    use UtilsTrait;

    /**
     * @var int
     */
    protected $bufferSize;

    /**
     * @var resource
     */
    protected $stream;

    /**
     * @var bool
     */
    protected $readable;

    /**
     * @var bool
     */
    protected $writable;

    /**
     * @var bool
     */
    protected $closing;

    /**
     * @var EventLoopInterface
     */
    protected $loop;

    /**
     * @var Buffer
     */
    protected $buffer;

    /**
     * Stream constructor.
     *
     * @param resource           $stream
     * @param EventLoopInterface $loop
     * @param int                $bufferSize
     */
    public function __construct($stream, EventLoopInterface $loop, int $bufferSize = 4096)
    {
        $this->stream = $stream;

        if (false === is_resource($this->stream) || "stream" !== get_resource_type($this->stream)) {
            throw new InvalidArgumentException('First parameter must be a valid stream resource');
        }

        $this->bufferSize = $bufferSize;
        $this->readable   = true;
        $this->writable   = true;
        $this->closing    = false;

        stream_set_blocking($this->stream, 0);
        stream_set_read_buffer($this->stream, 0);

        $this->loop   = $loop;
        $this->buffer = new Buffer($this->stream, $this->loop);

        $this->buffer->on('error', function ($error) {
            $this->emit('error', [$error, $this]);
            $this->close();
        });

        $this->buffer->on('drain', function () {
            $this->emit('drain', [$this]);
        });

        $this->resume();
    }

    /**
     * @inheritdoc
     */
    public function isReadable() : bool
    {
        return $this->readable;
    }

    /**
     * @inheritdoc
     */
    public function isWritable() : bool
    {
        return $this->writable;
    }

    /**
     * @inheritdoc
     */
    public function pause() : self
    {
        $this->loop->removeReadStream($this->stream);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function resume() : self
    {
        if ($this->isReadable()) {
            $this->loop->addReadStream($this->stream, [$this, 'handleData']);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function write($data)
    {
        if (false === $this->isWritable()) {
            return;
        }

        return $this->buffer->write($data);
    }

    /**
     * @inheritdoc
     */
    public function close() : self
    {
        if (false === $this->isReadable() && false === $this->closing) {
            return $this;
        }

        $this->closing = false;

        $this->readable = false;
        $this->writable = false;

        $this->emit('end', [$this]);
        $this->emit('close', [$this]);
        $this->loop->removeStream($this->stream);
        $this->buffer->removeListeners();
        $this->removeListeners();

        $this->handleClose();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function end($data = null) : self
    {
        if (false === $this->isWritable()) {
            return $this;
        }

        $this->closing  = true;
        $this->readable = false;
        $this->writable = false;

        $this->buffer->on('close', [$this, 'close']);

        $this->buffer->end($data);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function pipe(WritableStreamInterface $destination, array $options = [])
    {
        $this->pipeAll($this, $destination, $options);

        return $destination;
    }

    /**
     * @param resource $stream
     */
    public function handleData($stream)
    {
        $data = fread($stream, $this->bufferSize);

        $this->emit('data', [$data, $this]);

        if (false === is_resource($stream) || feof($stream)) {
            $this->end();
        }
    }

    public function handleClose()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * @return int
     */
    public function getBufferSize() : int
    {
        return $this->bufferSize;
    }

    /**
     * @param int $bufferSize
     *
     * @return $this
     */
    public function setBufferSize(int $bufferSize) : self
    {
        $this->bufferSize = $bufferSize;

        return $this;
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }
}
