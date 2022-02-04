<?php

namespace Tests\ExampleClasses;

class Bar implements BarInterface
{
    public FooInterface $foo;
    public function __construct(FooInterface $foo)
    {
        $this->foo = $foo;
    }
}