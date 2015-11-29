<?php

namespace Thruster\Component\Stream\Tests;

/**
 * Class TestCase
 *
 * @package Thruster\Component\Stream\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function expectCallableExactly($amount)
    {
        $mock = $this->createCallableMock();

        $mock
            ->expects($this->exactly($amount))
            ->method('someMethod');

        return [$mock, 'someMethod'];
    }

    protected function expectCallableOnce()
    {
        $mock = $this->createCallableMock();

        $mock->expects($this->once())
            ->method('someMethod');

        return [$mock, 'someMethod'];
    }

    protected function expectCallableNever()
    {
        $mock = $this->createCallableMock();

        $mock
            ->expects($this->never())
            ->method('someMethod');

        return [$mock, 'someMethod'];
    }

    protected function createCallableMock()
    {
        return $this->getMock(__CLASS__);
    }

    public function someMethod()
    {
    }
}
