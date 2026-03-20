<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Core;

use ReflectionClass;
use RuntimeException;

final class Container
{
    /**
     * @var array<string, array{factory: callable(self): mixed, shared: bool}>
     */
    private array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    public function bind(string $id, callable $factory, bool $shared = true): void
    {
        $this->bindings[$id] = [
            'factory' => $factory,
            'shared' => $shared,
        ];
    }

    public function singleton(string $id, callable $factory): void
    {
        $this->bind($id, $factory, true);
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances)
            || array_key_exists($id, $this->bindings)
            || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (array_key_exists($id, $this->bindings)) {
            $binding = $this->bindings[$id];
            $resolved = $binding['factory']($this);

            if ($binding['shared']) {
                $this->instances[$id] = $resolved;
            }

            return $resolved;
        }

        if (class_exists($id)) {
            return $this->build($id);
        }

        throw new RuntimeException(sprintf('Service "%s" is not bound in the container.', $id));
    }

    /**
     * @return object
     */
    private function build(string $class): object
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException(sprintf('Class "%s" is not instantiable.', $class));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            $instance = $reflection->newInstance();
            $this->instances[$class] = $instance;

            return $instance;
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type !== null && !$type->isBuiltin()) {
                $dependencies[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                sprintf('Unable to resolve parameter "$%s" for class "%s".', $parameter->getName(), $class)
            );
        }

        $instance = $reflection->newInstanceArgs($dependencies);
        $this->instances[$class] = $instance;

        return $instance;
    }
}
