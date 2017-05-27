<?php

namespace Dgame\Object;

use Dgame\Variants\Variants;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use function Dgame\Ensurance\enforce;

/**
 * Class ObjectFacade
 * @package Dgame\Object
 */
class ObjectFacade
{
    /**
     * @var object
     */
    private $object;
    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * ObjectFacade constructor.
     *
     * @param object $object
     */
    public function __construct($object)
    {
        enforce(is_object($object))->orThrow('That is not a valid object');

        $this->object = $object;
    }

    /**
     * @return object
     */
    final public function getObject()
    {
        return $this->object;
    }

    /**
     * @return ReflectionClass
     */
    final public function getReflection(): ReflectionClass
    {
        if ($this->reflection === null) {
            $this->reflection = new ReflectionClass($this->object);
        }

        return $this->reflection;
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return bool
     */
    final public function setValue(string $name, $value): bool
    {
        foreach (Variants::ofArguments($name)->withCamelSnakeCase() as $attribute) {
            if ($this->setValueByProperty($attribute, $value) || $this->setValueByMethod($attribute, $value)) {
                return true;
            }
        }

        return $this->tryMagicSet($name, $value);
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return bool
     */
    private function tryMagicSet(string $name, $value): bool
    {
        if ($this->hasMethod('__set')) {
            $this->invokeMethod('__set', $name, $value);

            return true;
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    final public function getValue(string $name)
    {
        foreach (Variants::ofArguments($name)->withCamelSnakeCase() as $attribute) {
            $property = $this->getPropertyByName($attribute);
            if ($property !== null && Validator::new($this)->isValidProperty($property)) {
                return $property->getValue($this->object);
            }

            $method = $this->getGetterMethod($name);
            if ($method !== null && Validator::new($this)->isValidGetterMethod($method)) {
                return $method->invoke($this->object);
            }
        }

        return $this->invokeMethod('__get', $name);
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return bool
     */
    final public function setValueByProperty(string $name, $value): bool
    {
        $property = $this->getPropertyByName($name);
        if ($property !== null && Validator::new($this)->isValidProperty($property)) {
            $property->setValue($this->object, $value);

            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return bool
     */
    final public function setValueByMethod(string $name, $value): bool
    {
        $method = $this->getSetterMethod($name);
        if ($method !== null && Validator::new($this)->isValidSetterMethod($method, $value)) {
            $method->invoke($this->object, $value);

            return true;
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    final public function getValueByMethod(string $name)
    {
        $method = $this->getGetterMethod($name);
        if ($method !== null && Validator::new($this)->isValidGetterMethod($method)) {
            return $method->invoke($this->object);
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    final public function getValueByProperty(string $name)
    {
        $property = $this->getPropertyByName($name);
        if ($property !== null && Validator::new($this)->isValidProperty($property)) {
            return $property->getValue($this->object);
        }

        return null;
    }

    /**
     * @param string $postfix
     *
     * @return null|ReflectionMethod
     */
    final public function getSetterMethod(string $postfix)
    {
        return $this->getMethod($postfix, ['set', 'append']);
    }

    /**
     * @param string $postfix
     *
     * @return null|ReflectionMethod
     */
    final public function getGetterMethod(string $postfix)
    {
        return $this->getMethod($postfix, ['get']);
    }

    /**
     * @param string $postfix
     * @param array  $prefixe
     *
     * @return null|ReflectionMethod
     */
    final public function getMethod(string $postfix, array $prefixe)
    {
        foreach ($prefixe as $prefix) {
            $method = $this->getMethodByName($prefix . ucfirst($postfix));
            if ($method !== null) {
                return $method;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param array  ...$args
     *
     * @return mixed|null
     */
    final public function invokeMethod(string $name, ...$args)
    {
        $method = $this->getMethodByName($name);
        if ($method !== null && Validator::new($this)->areValidMethodArguments($method, ...$args)) {
            return $method->invokeArgs($this->object, $args);
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return null|ReflectionMethod
     */
    final public function getMethodByName(string $name)
    {
        return $this->hasMethod($name) ? $this->getReflection()->getMethod($name) : null;
    }

    /**
     * @param string $name
     *
     * @return null|ReflectionProperty
     */
    final public function getPropertyByName(string $name)
    {
        return $this->hasProperty($name) ? $this->getReflection()->getProperty($name) : null;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    final public function hasProperty(string $name): bool
    {
        return $this->getReflection()->hasProperty($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    final public function hasMethod(string $name): bool
    {
        return $this->getReflection()->hasMethod($name);
    }
}
