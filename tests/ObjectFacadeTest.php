<?php

namespace Dgame\Object\Test;

use Dgame\Object\ObjectFacade;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Class ObjectFacadeTest
 * @package Dgame\Object\Test
 */
final class ObjectFacadeTest extends TestCase
{
    public function testHasMethod()
    {
        $this->assertTrue($this->new(Exception::class)->hasMethod('getMessage'));
        $this->assertFalse($this->new(Exception::class)->hasMethod('foo'));
    }

    public function testHasProperty()
    {
        foreach (['message', 'code', 'file', 'line'] as $property) {
            $this->assertTrue($this->new(Exception::class)->hasProperty($property));
        }
        $this->assertFalse($this->new(Exception::class)->hasProperty('foo'));
    }

    public function testGetPropertyByName()
    {
        foreach (['message', 'code', 'file', 'line'] as $name) {
            $property = $this->new(Exception::class)->getPropertyByName($name);

            $this->assertNotNull($property);
            $this->assertEquals($name, $property->getName());
            $this->assertNotEquals(0, ReflectionProperty::IS_PROTECTED & $property->getModifiers());
        }
    }

    public function testGetMethodByName()
    {
        $method = $this->new(Exception::class)->getMethodByName('getMessage');

        $this->assertNotNull($method);
        $this->assertEquals('getMessage', $method->getName());
        $this->assertNotEquals(0, (ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_FINAL) & $method->getModifiers());
    }

    public function testGetterMethod()
    {
        $method = $this->new(Exception::class)->getGetterMethod('message');

        $this->assertNotNull($method);
        $this->assertEquals('getMessage', $method->getName());
    }

    public function testSetterMethod()
    {
        $facade = new ObjectFacade(
            new class() {
                public function setFoo()
                {
                }
            }
        );

        $method = $facade->getSetterMethod('foo');

        $this->assertNotNull($method);
        $this->assertEquals('setFoo', $method->getName());
    }

    public function testGetValueByMethod()
    {
        $exception = new Exception('Test');
        $facade    = new ObjectFacade($exception);

        $this->assertEquals($exception, $facade->getObject());
        $this->assertEquals('Test', $facade->getValueByMethod('message'));
        $this->assertEquals($exception->getMessage(), $facade->getValueByMethod('message'));
        $this->assertEquals($exception->getFile(), $facade->getValueByMethod('file'));
        $this->assertEquals($exception->getLine(), $facade->getValueByMethod('line'));
        $this->assertNull($facade->getValueByMethod('unknown'));
    }

    public function testGetValueByProperty()
    {
        $facade = new ObjectFacade(
            new class() {
                public $foo = 42;
                public $bar = Exception::class;
            }
        );

        $this->assertEquals(42, $facade->getValueByProperty('foo'));
        $this->assertEquals(Exception::class, $facade->getValueByProperty('bar'));
        $this->assertNull($facade->getValueByProperty('unknown'));
        $this->assertNull($this->new(Exception::class)->getValueByProperty('line'));
        $this->assertNull($this->new(Exception::class)->getValueByProperty('file'));
    }

    public function testSetValueByMethod()
    {
        $facade = new ObjectFacade(
            new class() {
                private $foo = 42;
                private $bar;

                public function setFoo(int $foo)
                {
                    $this->foo = $foo;
                }

                public function setFooBar()
                {
                    $this->foo = 1;
                    $this->bar = 2;
                }

                public function getFoo(): int
                {
                    return $this->foo;
                }

                public function setBar(int $bar = null)
                {
                    $this->bar = $bar;
                }

                public function getBar()
                {
                    return $this->bar;
                }
            }
        );

        $this->assertEquals(42, $facade->getValueByMethod('foo'));
        $facade->setValueByMethod('foo', 23);
        $this->assertEquals(23, $facade->getValueByMethod('foo'));
        $facade->setValueByMethod('foo', null);
        $this->assertEquals(23, $facade->getValueByMethod('foo'));
        $facade->setValueByMethod('foo', 'abc');
        $this->assertEquals(23, $facade->getValueByMethod('foo'));
        $facade->setValue('foo', 3537);
        $this->assertEquals(3537, $facade->getValue('foo'));

        $this->assertNull($facade->getValueByMethod('bar'));
        $facade->setValueByMethod('bar', 1337);
        $this->assertEquals(1337, $facade->getValueByMethod('bar'));
        $facade->setValueByMethod('bar', null);
        $this->assertNull($facade->getValueByMethod('bar'));

        $facade->setValueByMethod('foobar', uniqid());
        $this->assertEquals(1, $facade->getValueByMethod('foo'));
        $this->assertEquals(2, $facade->getValueByMethod('bar'));
    }

    public function testSetValueByProperty()
    {
        $facade = new ObjectFacade(
            new class() {
                public $foo = 42;
            }
        );

        $this->assertEquals(42, $facade->getValueByProperty('foo'));
        $this->assertEquals($facade->getObject()->foo, $facade->getValueByProperty('foo'));
        $facade->setValueByProperty('foo', 23);
        $this->assertEquals(23, $facade->getValueByProperty('foo'));
        $this->assertEquals($facade->getObject()->foo, $facade->getValueByProperty('foo'));
        $facade->setValueByProperty('foo', null);
        $this->assertNull($facade->getValueByProperty('foo'));
        $this->assertEquals($facade->getObject()->foo, $facade->getValueByProperty('foo'));
        $facade->setValueByProperty('foo', 1337);
        $this->assertEquals(1337, $facade->getValueByProperty('foo'));
        $this->assertEquals($facade->getObject()->foo, $facade->getValueByProperty('foo'));
        $facade->setValue('foo', 3537);
        $this->assertEquals(3537, $facade->getValue('foo'));
    }

    private function new(string $class)
    {
        return new ObjectFacade(new $class());
    }
}