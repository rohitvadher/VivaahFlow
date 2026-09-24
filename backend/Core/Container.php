<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;

class Container
{
    private array $bindings = [];
    private array $resolved = [];

    public function __construct(array $bindings = [])
    {
        foreach ($bindings as $abstract => $concrete) {
            $this->bind($abstract, $concrete);
        }
    }

    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function fresh(string $abstract, array $params = [])
    {
        unset($this->resolved[$abstract]);
        return $this->make($abstract, $params);
    }

    public function make(string $class, array $params = [])
    {
        if (isset($this->resolved[$class])) {
            return $this->resolved[$class];
        }
        if (isset($this->bindings[$class])) {
            $binding = $this->bindings[$class];
            $instance = $binding instanceof Closure ? $binding($this) : $binding;
            return $this->resolved[$class] = $instance;
        }
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Cannot instantiate [$class].");
        }
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $this->resolved[$class] = $reflection->newInstance();
        }
        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->make($type->getName());
            } elseif (array_key_exists($parameter->getName(), $params)) {
                $arguments[] = $params[$parameter->getName()];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                $arguments[] = null;
            }
        }
        return $this->resolved[$class] = $reflection->newInstanceArgs($arguments);
    }
}