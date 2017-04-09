<?php

namespace Dgame\Object;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use function Dgame\Conditional\debug;
use function Dgame\Ensurance\enforce;

/**
 * Class ObjectFacade
 * @package Dgame\Object
 */
class ObjectFacade
{
    const DEBUG_LABEL = 'Dgame_Object_Facade';

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
    final public function setValueByProperty(string $name, $value): bool
    {
        $property = $this->getPropertyByName($name);
        if ($property !== null && $this->validateProperty($property)) {
            $property->setValue($this->object, $value);

            return true;
        }

        return false;
    }

    /**
     * @param ReflectionProperty $property
     *
     * @return bool
     */
    private function validateProperty(ReflectionProperty $property): bool
    {
        if (!$property->isPublic()) {
            debug(self::DEBUG_LABEL)->output('[Error] Property %s is not public', $property->getName());

            return false;
        }

        if ($property->isStatic()) {
            debug(self::DEBUG_LABEL)->output('[Error] Property %s is static', $property->getName());

            return false;
        }

        return true;
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
        if ($method !== null && $this->validateSetterMethod($method, $value)) {
            $method->invoke($this->object, $value);

            return true;
        }

        return false;
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return bool
     */
    private function validateMethod(ReflectionMethod $method): bool
    {
        if (!$method->isPublic()) {
            debug(self::DEBUG_LABEL)->output('[Error] Method %s is not public', $method->getName());

            return false;
        }

        if ($method->isStatic()) {
            debug(self::DEBUG_LABEL)->output('[Error] Method %s is static', $method->getName());

            return false;
        }

        return true;
    }

    /**
     * @param ReflectionMethod $method
     * @param                  $value
     *
     * @return bool
     */
    private function validateSetterMethod(ReflectionMethod $method, $value): bool
    {
        if (!$this->validateMethod($method)) {
            return false;
        }

        if ($value === null && $method->getNumberOfParameters() !== 0 && !$method->getParameters()[0]->allowsNull()) {
            debug(self::DEBUG_LABEL)->output('[Error] First parameter of method %s is not allowed to be null', $method->getName());

            return false;
        }

        if ($method->getNumberOfParameters() === 0) {
            debug(self::DEBUG_LABEL)->output('[Warning] Method %s does not accept any parameters', $method->getName());
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    final public function getValueByMethod(string $name)
    {
        $method = $this->getGetterMethod($name);
        if ($method !== null && $this->validateGetterMethod($method)) {
            return $method->invoke($this->object);
        }

        return null;
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return bool
     */
    private function validateGetterMethod(ReflectionMethod $method): bool
    {
        if (!$this->validateMethod($method)) {
            return false;
        }

        $value = $method->invoke($this->object);
        if ($value === null && $method->hasReturnType() && !$method->getReturnType()->allowsNull()) {
            debug(self::DEBUG_LABEL)->output('[Error] Method %s return value is not allowed to be null', $method->getName());

            return false;
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    final public function getValueByProperty(string $name)
    {
        $property = $this->getPropertyByName($name);
        if ($property !== null && $this->validateProperty($property)) {
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
