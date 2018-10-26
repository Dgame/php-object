<?php

namespace Dgame\Object;

use Dgame\Type\Type;
use ReflectionMethod;
use ReflectionParameter;

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
     * @param ReflectionMethod $method
     * @param                  $value
     *
     * @return bool
     */
    public function isValidSetterMethod(ReflectionMethod $method, $value): bool
    {
        if (!$method->isPublic()) {
            return false;
        }

        if ($method->getNumberOfParameters() === 0) {
            return true;
        }

        if ($method->getNumberOfRequiredParameters() > 1) {
            return false;
        }

        if ($value === null) {
            return $method->getParameters()[0]->allowsNull();
        }

        return $this->isValidParameterValue($method->getParameters()[0], $value);
    }

    /**
     * @param ReflectionParameter $parameter
     * @param                     $value
     *
     * @return bool
     */
    public function isValidParameterValue(ReflectionParameter $parameter, $value): bool
    {
        return !$parameter->hasType() || Type::from($parameter)->accept($value);
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return bool
     */
    public function isValidGetterMethod(ReflectionMethod $method): bool
    {
        if (!$method->isPublic()) {
            return false;
        }

        try {
            $value = $method->invoke($this->facade->getObject());
        } catch (\Throwable $t) {
            return false;
        }

        return $value !== null || !$method->hasReturnType() || $method->getReturnType()->allowsNull();
    }

    /**
     * @param ReflectionMethod $method
     * @param array            ...$args
     *
     * @return bool
     */
    public function areValidMethodArguments(ReflectionMethod $method, ...$args): bool
    {
        if (!$method->isPublic()) {
            return false;
        }

        if (count($args) < $method->getNumberOfRequiredParameters()) {
            return false;
        }

        return $this->validateMethodArguments($method, ...$args);
    }

    /**
     * @param ReflectionMethod $method
     * @param array            ...$args
     *
     * @return bool
     */
    private function validateMethodArguments(ReflectionMethod $method, ...$args): bool
    {
        $parameters = $method->getParameters();
        foreach ($args as $i => $arg) {
            if (!array_key_exists($i, $parameters)) {
                break;
            }

            if ($arg === null && !$parameters[$i]->allowsNull()) {
                return false;
            }

            if (!$this->isValidParameterValue($parameters[$i], $arg)) {
                return false;
            }
        }

        return true;
    }
}
