<?php

namespace YRV\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    private array $definitions;
    private array $factories;
    private array $files;
    private array $aliases;
    private array $resolved;

    public function __construct()
    {
    }

    public function alias(string $id, $alias): void
    {
        $this->aliases[$id] = $alias;
    }

    public function set($id, $resolver): void
    {
        $this->definitions[$id] = $resolver;
    }

    public function factory($id, $resolver): void
    {
        $this->factories[$id] = $resolver;
    }

    public function file($id, $filename): void
    {
        $this->files[$id] = $filename;
    }

    public function call($id, ...$args)
    {
        $source = $this->getSource($id, $isFactory=false);

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
    public function get(string $id)
    {
        if (isset($this->resolved[$id]) || array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        return $this->resolve($id);
    }

    private function resolve($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('Dependecy [%s] not found', $id));
        }

        $source = &$this->getSource($id, $isFactory=false);

        if (is_scalar($source) || is_null($source)) {
            $this->resolved[$id] = $source;
            return $source;
        }

        if (!is_callable($source)) {
            throw new ContainerException(sprintf(
                'Container [%s] type is undefined',
                $id
            ));
        }

        try {
            $value = call_user_func($source, $this);
        } catch (\Throwable $exception) {
            throw new ContainerException(sprintf(
                'Container [%s] error: ' . $exception->getMessage(),
                $id
            ));
        }

        if (!$isFactory) {
            $this->resolved[$id] = $value;
        }

        return $value;
    }

    private function getSource(string $id, bool &$isFactory)
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        if (array_key_exists($id, $this->definitions)) {
            $source = &$this->definitions[$id];
        } elseif (array_key_exists($id, $this->factories)) {
            $source = &$this->factories[$id];
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
            isset($this->definitions[$id]) 
            || isset($this->factories[$id]) 
            || isset($this->files[$id]) 
            || isset($this->aliases[$id])
        );
    }
}