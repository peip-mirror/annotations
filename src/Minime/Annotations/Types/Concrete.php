<?php

namespace Minime\Annotations\Types;

use stdClass;
use ReflectionClass;
use Minime\Annotations\Interfaces\TypeInterface;
use Minime\Annotations\Types\Json;
use Minime\Annotations\ParserException;

class Concrete implements TypeInterface
{

    /**
     * Process a value to be a concrete annotation
     *
     * @param  string $value json string
     * @param  string $class name of concrete annotation type (class)
     * @return object
     */
    public function parse($value, $class = null)
    {
        if (! class_exists($class)) {
            throw new ParserException("Concrete annotation expects {$class} to exist.");
        }
        $prototype = (new Json)->parse($value);
        if (! $prototype instanceof stdClass) {
            throw new ParserException("Json value for annotation({$class}) must be of type object.");
        }
        if (! $this->isPrototypeSchemaValid($prototype)) {
            throw new ParserException("Only arrays should be used to configure concrete annotation method calls.");
        }

        return $this->makeInstance($class, $prototype);
    }

    public function makeInstance($class, stdClass $prototype)
    {
        $reflection = (new ReflectionClass($class));
        if (isset($prototype->__construct)) {
            $instance = $reflection->newInstanceArgs($prototype->__construct);
            unset($prototype->__construct);
        } else {
            $instance = $reflection->newInstance();
        }

        return $this->doMethodConfiguration($instance, $prototype);
    }

    public function doMethodConfiguration($instance, stdClass $prototype)
    {
        foreach ($prototype as $method => $args) {
            call_user_func_array([$instance, $method], $args);
        }

        return $instance;
    }

    public function isPrototypeSchemaValid($prototype)
    {
        foreach ($prototype as $method => $args) {
            if (! is_array($args)) {
                return false;
            }
        }

        return true;
    }

}
