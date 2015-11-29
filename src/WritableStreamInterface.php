<?php

namespace Thruster\Component\Stream;

use Thruster\Component\EventEmitter\EventEmitterInterface;

/**
 * Interface WritableStreamInterface
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface WritableStreamInterface extends StreamInterface, EventEmitterInterface
{
    /**
     * @return bool
     */
    public function isWritable() : bool;

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function write($data);

    /**
     * @param mixed $data
     *
     * @return $this
     */
    public function end($data = null);
}
