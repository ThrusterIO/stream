<?php

namespace Thruster\Component\Stream;

use Thruster\Component\EventEmitter\EventEmitterInterface;


/**
 * Trait UtilsTrait
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
trait UtilsTrait
{
    /**
     * @param ReadableStreamInterface $source
     * @param WritableStreamInterface $destination
     * @param array                   $options
     *
     * @return WritableStreamInterface
     */
    public function pipeAll(ReadableStreamInterface $source, WritableStreamInterface $destination, array $options = [])
    {
        // TODO: use stream_copy_to_stream
        // it is 4x faster than this
        // but can lose data under load with no way to recover it
        $destination->emit('pipe', array($source));

        $source->on('data', function ($data) use ($source, $destination) {
            $feedMore = $destination->write($data);
            if (false === $feedMore) {
                $source->pause();
            }
        });

        $destination->on('drain', function () use ($source) {
            $source->resume();
        });

        $end = isset($options['end']) ? $options['end'] : true;

        if ($end && $source !== $destination) {
            $source->on('end', function () use ($destination) {
                $destination->end();
            });
        }

        return $destination;
    }

    /**
     * @param EventEmitterInterface $source
     * @param EventEmitterInterface $target
     * @param array                 $events
     */
    public function forwardEvents(EventEmitterInterface $source, EventEmitterInterface $target, array $events)
    {
        foreach ($events as $event) {
            $source->on($event, function () use ($event, $target) {
                $target->emit($event, func_get_args());
            });
        }
    }
}
