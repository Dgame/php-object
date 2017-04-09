<?php

namespace Dgame\Object;

use Dgame\Type\Type;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use function Dgame\Conditional\debug;

/**
 * Class Validator
 * @package Dgame\Object
 */
final class Validator
{
    /**
     * @var ObjectFacade
     */
    private $facade;

    /**
     * Validator constructor.
     *
     * @param ObjectFacade $facade
     */
    public function __construct(ObjectFacade $facade)
    {
        $this->facade = $facade;
    }

    /**
     * @param ObjectFacade $facade
     *
     * @return Validator
     */
    public static function new(ObjectFacade $facade): self
    {
        return new self($facade);
    }

    /**
     * @param ReflectionProperty $property
     *
     * @return bool
     */
    public function isValidProperty(ReflectionProperty $property): bool
    {
        if (!$property->isPublic()) {
            debug(ObjectFacade::DEBUG_LABEL)->output('[Error] Property %s is not public', $property->getName());

            return false;
        }

        if ($property->isStatic()) {
            debug(ObjectFacade::DEBUG_LABEL)->output('[Error] Property %s is static', $property->getName());

            return false;
        }

        return true;
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return bool
     */
    public function isValidMethod(ReflectionMethod $method): bool
    {
        if (!$method->isPublic()) {
            debug(ObjectFacade::DEBUG_LABEL)->output('[Error] Method %s is not public', $method->getName());

            return false;
        }

        if ($method->isStatic()) {
            debug(ObjectFacade::DEBUG_LABEL)->output('[Error] Method %s is static', $method->getName());

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
    public function isValidSetterMethod(ReflectionMethod $method, $value): bool
    {
        if (!$this->isValidMethod($method)) {
            return false;
        }

        if ($method->getNumberOfParameters() === 0) {
            return true;
        }

        if ($method->getNumberOfRequiredParameters() > 1) {
            debug(ObjectFacade::DEBUG_LABEL)->output('[Error] Method %s need more than one parameter', $method->getName());

            return false;
        }

        if ($value === null) {
            if ($method->getParameters()[0]->allowsNull()) {
                return true;
            }

            debug(ObjectFacade::DEBUG_LABEL)->output('[Error] First parameter of method %s is not allowed to be null', $method->getName());

            return false;
        }

        if (!$this->isValidValue($method->getParameters()[0], $value)) {
            debug(ObjectFacade::DEBUG_LABEL)->output(
                '[Warning] Method %s does not accept value %s',
                $method->getName(),
                var_export($value, true)
            );

            return false;
        }

        return true;
    }

    /**
     * @param ReflectionParameter $parameter
     * @param                     $value
     *
     * @return bool
     */
    public function isValidValue(ReflectionParameter $parameter, $value): bool
    {
        return !$parameter->hasType() || Type::from($parameter)->accept($value);
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return bool
     */
    public function validateGetterMethod(ReflectionMethod $method): bool
    {
        if (!$this->isValidMethod($method)) {
            return false;
        }

        $value = $method->invoke($this->facade->getObject());
        if ($value === null && $method->hasReturnType() && !$method->getReturnType()->allowsNull()) {
            debug(ObjectFacade::DEBUG_LABEL)->output('[Error] Method %s return value is not allowed to be null', $method->getName());

            return false;
        }

        return true;
    }
}