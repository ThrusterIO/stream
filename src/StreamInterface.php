<?php

namespace Thruster\Component\Stream;

/**
 * Interface StreamInterface
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface StreamInterface
{
    /**
     * @return $this
     */
    public function close();
}
