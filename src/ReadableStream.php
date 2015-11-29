<?php

namespace Thruster\Component\Stream;

/**
 * Class ReadableStream
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class ReadableStream extends BaseStream implements ReadableStreamInterface
{
    public function __construct()
    {
        $this->closed = false;
    }

    /**
     * {@inheritDoc}
     */
    public function isReadable() : bool
    {
        return !$this->closed;
    }

    /**
     * {@inheritDoc}
     */
    public function pause() : self
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resume() : self
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function pipe(WritableStreamInterface $dest, array $options = [])
    {
        $this->pipeAll($this, $dest, $options);

        return $dest;
    }
}
