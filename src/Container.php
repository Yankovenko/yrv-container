<?php

namespace YRV\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionException;

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

    /**
     * @throws ContainerException
     */
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
     * @return mixed Entry.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     */
    public function get(string $id, array $args = [])
    {
        if (isset($this->resolved[$id]) || array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('Dependency [%s] not found', $id));
        }

        $this->processedResolved = $id;

        $isFactory = false;
        $source = $this->getSource($id, $isFactory);

        if (is_numeric($source) || is_null($source) || is_bool($source)
            || (is_string($source) && !class_exists($source))
            || (is_array($source) && !is_callable($source))
        ) {
            $this->resolved[$id] = $source;
            return $source;
        }

        try {
            $result = $this->resolve($source, $args);
        } catch (\Throwable $exception) {
            throw new ContainerException(sprintf(
                'Container [%s] error: ' . $exception->getMessage(),
                $id
            ));
        }

        if (!$isFactory) {
            $this->resolved[$id] = $result;
        }

        return $result;
    }

    /**
     * @throws ContainerException
     */
    public function resolve($source, array $args = [])
    {
        try {
            if (is_string($source) && class_exists($source)) {
                return $this->resolveObject($source, $args);
            } elseif (is_callable($source) || (is_string($source) && function_exists($source))) {
                return $this->resolveCallable($source, $args);
            }
        } catch (\Throwable $exception) {
            throw new ContainerException('Error resolve: ' . $exception->getMessage());
        }
        throw new ContainerException('Type undefined');
    }

    /**
     * @throws ContainerException
     */
    private function resolveObject(string $id, array $args = []): object
    {
        try {
            $reflector = new \ReflectionClass($id);

            if (!$reflector->isInstantiable()) {
                throw new \InvalidArgumentException("$id is not instantiable");
            }

            $constructor = $reflector->getConstructor();
            if (!$constructor) {
                return $reflector->newInstance();
            }
            $params = $constructor->getParameters();
            $newParams = $this->resolveParameters($params, $args);
            return $reflector->newInstance(...$newParams);

        } catch (ReflectionException $e) {
            throw new ContainerException(
                sprintf(
                    'Error resolve object [%s]: %s',
                    $id,
                    $e->getMessage()
                ));
        }
    }

    /**
     * @throws ContainerException
     */
    private function resolveCallable($action, array $args = [])
    {
        try {
            if (is_array($action)) {
                $reflection = new \ReflectionFunction(\Closure::fromCallable([$action[0], $action[1]]));
            } else {
                $reflection = new \ReflectionFunction($action);
            }
        } catch (ReflectionException $exception) {
            throw new ContainerException ('Error reflection callable');
        }

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

            if (isset($args[$parameterName]) || array_key_exists($parameterName, $args)) {
                $newParams[] = $args[$parameterName];
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $newParams[] = $param->getDefaultValue();
                continue;
            }

            if ($param->isOptional()) {
                break;
            }


            $name = (string)$param->getType();
            if ($this->has($name)) {
                if ($name === $this->processedResolved) {
                    throw new ContainerException(sprintf(
                        'Circular dependency of [%s]',
                        $this->processedResolved
                    ));
                }
                $newParams[] = $this->get($name);
                continue;
            }
            throw new ContainerException('Can not resolver parameter [' . $parameterName . '], type of [' . $name . ']');
        }

        return $newParams;
    }

    /**
     * @throws ContainerException
     */
    private function getSource(string $id, bool &$isFactory)
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        $source = null;

        if (array_key_exists($id, $this->definitions)) {
            $source = $this->definitions[$id];
        } elseif (array_key_exists($id, $this->factories)) {
            $source = $this->factories[$id];
            $isFactory = true;
        } elseif (array_key_exists($id, $this->files)) {
            if (!file_exists($this->files[$id])) {
                throw new ContainerException(
                    sprintf(
                        'File [%s] of definition [%s] not exist',
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
    public function has(string $id): bool
    {
        return (
            isset($this->resolved[$id])
            || isset($this->definitions[$id])
            || isset($this->factories[$id])
            || isset($this->files[$id])
            || isset($this->aliases[$id])
        );
    }

    public function unset(string $id)
    {
        if (!$this->has($id)) {
            return;
        }
        foreach (['resolved', 'definitions', 'factories', 'files', 'aliases'] as $val) {
            if (array_key_exists($id, $this->$val)) {
                unset($this->$val[$id]);
            }
        }
    }
}