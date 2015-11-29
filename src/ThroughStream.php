<?php

namespace Thruster\Component\Stream;

/**
 * Class ThroughStream
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class ThroughStream extends CompositeStream
{
    public function __construct()
    {
        $readable = new ReadableStream();
        $writable = new WritableStream();

        parent::__construct($readable, $writable);
    }

    public function filter($data)
    {
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function write($data)
    {
        $this->readable->emit('data', [$this->filter($data), $this]);
    }

    /**
     * {@inheritDoc}
     */
    public function end($data = null)
    {
        if (null !== $data) {
            $this->readable->emit('data', [$this->filter($data), $this]);
        }

        $this->writable->end($data);
    }
}
