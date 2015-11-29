<?php

namespace Thruster\Component\Stream;

use Thruster\Component\EventEmitter\EventEmitterTrait;

/**
 * Class WritableStream
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class WritableStream extends BaseStream implements WritableStreamInterface
{
    public function __construct()
    {
        $this->closed = false;
    }

    /**
     * {@inheritDoc}
     */
    public function write($data)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function end($data = null) : self
    {
        if (null !== $data) {
            $this->write($data);
        }

        $this->close();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable() : bool
    {
        return !$this->closed;
    }
}
