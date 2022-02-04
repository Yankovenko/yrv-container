<?php

namespace YRV\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    private array $definitions = [];
    private array $factories = [];
    private array $files = [];
    private array $aliases = [];
    private array $resolved = [];

    private string $processedResolved;

    public function __construct()
    {
        $this->resolved[self::class] = $this;
        $this->resolved[ContainerInterface::class] = $this;
    }

    public function alias(string $id, $alias): void
    {
        $this->aliases[$id] = $alias;
    }

    public function set(string $id, $resolver): void
    {
        $this->definitions[$id] = $resolver;
    }

    public function factory(string $id, $resolver): void
    {
        $this->factories[$id] = $resolver;
    }

    public function file(string $id, $filename): void
    {
        $this->files[$id] = $filename;
    }

    public function call(string $id, ...$args)
    {
        $isFactory = false;
        $source = $this->getSource($id, $isFactory);

        if (!is_callable($source)) {
            throw new ContainerException(sprintf(
                'Container [%s] is not callable',
                $id
            ));
        }

        return call_user_func($source, ...$args);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */ 
    public function get($id, array $args = [])
    {
        if (isset($this->resolved[$id]) || array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        $this->processedResolved = $id;
        return $this->resolve($id, $args);
    }

    private function resolve($id, array $args = [])
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('Dependecy [%s] not found', $id));
        }

        $isFactory = false;
        $source = $this->getSource($id, $isFactory);

        if (is_numeric($source) || is_null($source) || (is_object($source) && get_class($source) !== 'Closure')) {
            $this->resolved[$id] = $source;
            return $source;
        }

        if (is_string($source) && class_exists($source)) {
            $value = $this->resolveObject($source, $args);

        } elseif (is_string($source) && function_exists($source)) {
            $value = $this->resolveFunction($source, $args);

        } elseif (is_string($source)) {
            $this->resolved[$id] = $source;
            return $source;

        } elseif (!is_callable($source)) {
            throw new ContainerException(sprintf(
                'Container [%s] type is undefined',
                $id
            ));
        } else {

            try {
                $value = $this->resolveCallable($source, $args);
            } catch (\Throwable $exception) {
                throw new ContainerException(sprintf(
                    'Container [%s] error: ' . $exception->getMessage(),
                    $id
                ));
            }
        }

        if (!$isFactory) {
            $this->resolved[$id] = $value;
        }

        return $value;
    }

    private function resolveObject(string $id, array $args = []): object
    {
        try {
            $reflector = new \ReflectionClass($id);
        } catch (\ReflectionException $e) {
            throw new ContainerException(
                strintf(
                    'Class [%s] does not exists: %s',
                    $id,
                    $e->getMessage()
                ),
                null, $e);
        }

        if(!$reflector->isInstantiable()) {
            throw new \InvalidArgumentException("$name is not instantiable");
        }

        $constructor = $reflector->getConstructor();
        if (!$constructor) {
            return $reflector->newInstance();
        }
        $params = $constructor->getParameters();
        $newParams = $this->resolveParameters($params, $args);
        return $reflector->newInstance(...$newParams);
    }

    public function resolveCallable($action, array $args = [])
    {
        if (is_array($action)) {
            $reflection = new \ReflectionFunction(\Closure::fromCallable([$action[0], $action[1]]));
        }

        $reflection = new \ReflectionFunction($action);

        $params = $this->resolveParameters($reflection->getParameters(), $args);

        return $reflection->invoke(...$params);
    }


    /**
     * @param \ReflectionParameter[] $params
     * @return array
     */
    private function resolveParameters(array $params, array $args = []): array
    {
        $newParams = [];

        foreach ($params as $param) {
            $parameterName = $param->getName();

            if(isset($args[$parameterName]) || array_key_exists($parameterName, $args)) {
                $newParams[] = $args[$parameterName];
                continue;
            }

            if($param->isDefaultValueAvailable()) {
                $newParams[] = $param->getDefaultValue();
                continue;
            }

            if ($param->isOptional()) {
                break;
            }

            if ($name === $this->processedResolved) {
                throw new ContainerException(sprintf(
                    'Circular dependency of [%s]',
                    $this->processedResolved
                ));
            }

            $name = (string) $param->getType();
            if ($this->has($name)) {
                $newParams[] = $this->get($name);
                continue;
            }
            print_r (array_keys($this->resolved));
            throw new ContainerException('Can not resolver parameter ['.$parameterName.'], type of ['.$name.']');
        }

        return $newParams;
    }

    private function getSource(string $id, bool &$isFactory)
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        if (array_key_exists($id, $this->definitions)) {
            $source = $this->definitions[$id];
        } elseif (array_key_exists($id, $this->factories)) {
            $source = $this->factories[$id];
            $isFactory = true;
        } elseif (array_key_exists($id, $this->files)) {
            if (!file_exists($this->files[$id])) {
                throw new ContainerException(
                    sprintf(
                        'File [%s] of defination [%s] not exist',
                        $this->files[$id],
                        $id
                    )
                );
            }
            $source = include $this->files[$id];
        }
        return $source;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id)
    {
        return (
            isset($this->resolved[$id])
            || isset($this->definitions[$id])
            || isset($this->factories[$id]) 
            || isset($this->files[$id]) 
            || isset($this->aliases[$id])
        );
    }
}