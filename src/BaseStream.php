<?php

namespace Thruster\Component\Stream;

use Thruster\Component\EventEmitter\EventEmitterTrait;

/**
 * Class BaseStream
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class BaseStream implements StreamInterface
{
    use EventEmitterTrait;
    use UtilsTrait;

    /**
     * @var bool
     */
    protected $closed;

    /**
     * {@inheritDoc}
     */
    public function close() : self
    {
        if ($this->closed) {
            return $this;
        }

        $this->closed = true;
        $this->emit('end', [$this]);
        $this->emit('close', [$this]);
        $this->removeListeners();

        return $this;
    }
}
