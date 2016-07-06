<?php


namespace Application;

use H4D\Leveret\Service\ServiceContainer;
use H4D\Leveret\Service\ServiceInterface;

class ServiceContainerTest extends \PHPUnit_Framework_TestCase
{

    public function valuesProvider()
    {
        return [
            ['a', 'hello!', false],
            ['b', 123, false],
            ['c', 12.3, false],
            ['d', null, false],
            ['e', true, false],
            ['f', false, false],
            ['g', [1, 2, 3], false],
            ['h', new \ArrayObject([1, 2, 3]), false]
        ];
    }

    /**
     * @dataProvider valuesProvider
     *
     * @param string $key
     * @param mixed $value
     * @param bool $singleton
     *
     * @throws \H4D\Leveret\Exception\ServiceContainerException
     */
    public function test_registerAndGet($key, $value, $singleton)
    {
        ServiceContainer::i()->register($key, $value, $singleton);
        $this->assertEquals($value, ServiceContainer::i()->get($key));
    }

    public function test_registerCallableAndGet_noSingleton()
    {
        $key = 'callable';
        $callable = function ()
        {
            $microtime = microtime(true);
            return $microtime;
        };
        
        ServiceContainer::i()->registerCallable($key, $callable, false);
        $contents1 = ServiceContainer::i()->get($key);
        usleep(1000);
        $contents2 = ServiceContainer::i()->get($key);
        $this->assertTrue(is_numeric($contents1));
        $this->assertTrue(is_numeric($contents2));
        $this->assertTrue($contents1 !== $contents2);
    }

    public function test_registerCallableAndGet_singleton()
    {
        $key = 'callableSingleton';
        $callable = function ()
        {
            $microtime = microtime(true);
            return $microtime;
        };

        ServiceContainer::i()->registerCallable($key, $callable, true);
        $contents1 = ServiceContainer::i()->get($key);
        usleep(1000);
        $contents2 = ServiceContainer::i()->get($key);
        $this->assertTrue(is_numeric($contents1));
        $this->assertTrue(is_numeric($contents2));
        $this->assertTrue($contents1 == $contents2);
    }

    public function test_getRegisteredServices()
    {
        $services = ServiceContainer::i()->getRegisteredServices();
        foreach ($services as $name=>$value)
        {
            $this->assertTrue($value instanceof ServiceInterface);
        }
    }

    public function test_reset()
    {
        ServiceContainer::i()->register('foo', 'bar');
        ServiceContainer::i()->reset();
        $services = ServiceContainer::i()->getRegisteredServices();
        $this->assertEquals(0, count($services));
    }

    public function test_getRegisteredServicesNames()
    {
        $returnedValue = ServiceContainer::i()->reset();
        $this->assertTrue($returnedValue instanceof ServiceContainer);
        $expected = ['foo', 'hello'];
        ServiceContainer::i()->register('foo', 'bar');
        ServiceContainer::i()->register('hello', 'world');
        $servicesNames = ServiceContainer::i()->getRegisteredServicesNames();
        $this->assertEquals($expected, $servicesNames);
    }

    /**
     * @expectedException \H4D\Leveret\Exception\ServiceContainerException
     */
    public function test_registerValues_throwsException()
    {
        ServiceContainer::i()->registerValue('hola', new \ArrayObject([]));
    }

    /**
     * @expectedException \H4D\Leveret\Exception\ServiceContainerException
     */
    public function test_registerCallable_throwsException()
    {
        ServiceContainer::i()->registerCallable('noCallable', 'test');
    }

    /**
     * @expectedException \H4D\Leveret\Exception\ServiceContainerException
     */
    public function test_registerResource_throwsException()
    {
        /** @noinspection PhpParamsInspection */
        ServiceContainer::i()->registerResource('noResource', 'test');
    }

    /**
     * @expectedException \H4D\Leveret\Exception\ServiceContainerException
     */
    public function test_registerInstance_throwsException()
    {
        /** @noinspection PhpParamsInspection */
        ServiceContainer::i()->registerInstance('noObject', 'test');
    }

}