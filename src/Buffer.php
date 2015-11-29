<?php

namespace Thruster\Component\Stream;

use Thruster\Component\EventEmitter\EventEmitterInterface;
use Thruster\Component\EventEmitter\EventEmitterTrait;
use Thruster\Component\EventLoop\EventLoopInterface;

/**
 * Class Buffer
 *
 * @package Thruster\Component\Stream
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Buffer implements WritableStreamInterface
{
    use EventEmitterTrait;

    /**
     * @var resource
     */
    protected $stream;

    /**
     * @var bool
     */
    protected $listening;

    /**
     * @var int
     */
    protected $softLimit;

    /**
     * @var bool
     */
    protected $writable;

    /**
     * @var EventLoopInterface
     */
    protected $loop;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var array
     */
    protected $lastError;

    /**
     * Buffer constructor.
     *
     * @param resource           $stream
     * @param EventLoopInterface $loop
     * @param int                $softLimit
     */
    public function __construct($stream, EventLoopInterface $loop, int $softLimit = 2048)
    {
        $this->stream    = $stream;
        $this->loop      = $loop;
        $this->setSoftLimit($softLimit);

        $this->setListening(false);
        $this->writable  = true;
        $this->data      = '';

        $this->lastError = [
            'number'  => 0,
            'message' => '',
            'file'    => '',
            'line'    => 0,
        ];
    }

    /**
     * @return int
     */
    public function getSoftLimit() : int
    {
        return $this->softLimit;
    }

    /**
     * @param int $softLimit
     *
     * @return $this
     */
    public function setSoftLimit(int $softLimit) : self
    {
        $this->softLimit = $softLimit;

        return $this;
    }

    /**
     * @return bool
     */
    public function isListening() : bool
    {
        return $this->listening;
    }

    /**
     * @param bool $listening
     *
     * @return $this
     */
    public function setListening(bool $listening) : self
    {
        $this->listening = $listening;

        return $this;
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
    public function write($data)
    {
        if (!$this->writable) {
            return;
        }

        $this->data .= $data;

        if (false === $this->isListening()) {
            $this->setListening(true);

            $this->loop->addWriteStream($this->stream, [$this, 'handleWrite']);
        }

        $belowSoftLimit = strlen($this->data) < $this->getSoftLimit();

        return $belowSoftLimit;
    }

    /**
     * @inheritdoc
     */
    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        $this->writable = false;

        if ($this->isListening()) {
            $this->on('full-drain', [$this, 'close']);
        } else {
            $this->close();
        }
    }

    /**
     * @inheritdoc
     */
    public function close() : self
    {
        $this->writable  = false;
        $this->setListening(false);
        $this->data      = '';

        $this->emit('close', [$this]);

        return $this;
    }

    public function handleWrite()
    {
        if (!is_resource($this->stream)) {
            $this->emit('error', [new \RuntimeException('Tried to write to invalid stream.'), $this]);

            return;
        }

        set_error_handler([$this, 'errorHandler']);

        $sent = fwrite($this->stream, $this->data);

        restore_error_handler();

        if (false === $sent) {
            $this->emit('error', [
                new \ErrorException(
                    $this->lastError['message'],
                    0,
                    $this->lastError['number'],
                    $this->lastError['file'],
                    $this->lastError['line']
                ),
                $this
            ]);

            return;
        }

        if (0 === $sent && feof($this->stream)) {
            $this->emit('error', [new \RuntimeException('Tried to write to closed stream.'), $this]);

            return;
        }

        $len        = strlen($this->data);
        $this->data = (string)substr($this->data, $sent);

        if ($len >= $this->softLimit && $len - $sent < $this->getSoftLimit()) {
            $this->emit('drain', [$this]);
        }

        if (0 === strlen($this->data)) {
            $this->loop->removeWriteStream($this->stream);
            $this->setListening(false);

            $this->emit('full-drain', [$this]);
        }
    }

    protected function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $this->lastError['number']  = $errno;
        $this->lastError['message'] = $errstr;
        $this->lastError['file']    = $errfile;
        $this->lastError['line']    = $errline;
    }
}
