<?php

namespace Thruster\Component\Stream;

use Thruster\Component\EventEmitter\EventEmitterInterface;

/**
 * Interface ReadableStreamInterface
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface ReadableStreamInterface extends StreamInterface, EventEmitterInterface
{
    /**
     * @return bool
     */
    public function isReadable() : bool;

    /**
     * @return $this
     */
    public function pause();

    /**
     * @return $this
     */
    public function resume();

    /**
     * @param WritableStreamInterface $destination
     * @param array                   $options
     *
     * @return mixed
     */
    public function pipe(WritableStreamInterface $destination, array $options = []);
}
