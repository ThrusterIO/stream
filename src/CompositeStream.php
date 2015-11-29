<?php

namespace Thruster\Component\Stream;

use Thruster\Component\EventEmitter\EventEmitterTrait;

/**
 * Class CompositeStream
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class CompositeStream implements DuplexStreamInterface
{
    use EventEmitterTrait;
    use UtilsTrait;

    /**
     * @var ReadableStreamInterface
     */
    protected $readable;

    /**
     * @var WritableStreamInterface
     */
    protected $writable;

    protected $pipeSource;

    /**
     * @param ReadableStreamInterface $readable
     * @param WritableStreamInterface $writable
     */
    public function __construct(ReadableStreamInterface $readable, WritableStreamInterface $writable)
    {
        $this->readable = $readable;
        $this->writable = $writable;

        $this->forwardEvents($this->readable, $this, ['data', 'end', 'error', 'close']);
        $this->forwardEvents($this->writable, $this, ['drain', 'error', 'close', 'pipe']);

        $this->readable->on('close', [$this, 'close']);
        $this->writable->on('close', [$this, 'close']);

        $this->on('pipe', [$this, 'handlePipeEvent']);
    }

    /**
     * @param $source
     *
     * @return CompositeStream
     */
    public function handlePipeEvent($source) : self
    {
        $this->pipeSource = $source;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isReadable() : bool
    {
        return $this->readable->isReadable();
    }

    /**
     * {@inheritDoc}
     */
    public function pause() : self
    {
        if ($this->pipeSource) {
            $this->pipeSource->pause();
        }

        $this->readable->pause();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resume() : self
    {
        if ($this->pipeSource) {
            $this->pipeSource->resume();
        }

        $this->readable->resume();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function pipe(WritableStreamInterface $dest, array $options = []) : WritableStreamInterface
    {
        $this->pipeAll($this, $dest, $options);

        return $dest;
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable() : bool
    {
        return $this->writable->isWritable();
    }

    /**
     * {@inheritDoc}
     */
    public function write($data)
    {
        return $this->writable->write($data);
    }

    /**
     * {@inheritDoc}
     */
    public function end($data = null) : self
    {
        $this->writable->end($data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function close() : self
    {
        $this->pipeSource = null;

        $this->readable->close();
        $this->writable->close();

        return $this;
    }
}
