<?php


namespace Tests;


use Tests\ExampleClasses\Bar;
use Tests\ExampleClasses\BarInterface;
use Tests\ExampleClasses\Foo;
use Tests\ExampleClasses\FooInterface;
use YRV\Container\Container;

class ClassesTest extends \PHPUnit\Framework\TestCase

{

    public function testInvokeClass()
    {
        $container = new Container();
        $container->set(FooInterface::class, Foo::class);
        $fooInstance = $container->get(FooInterface::class);
        $this->assertInstanceOf(Foo::class, $fooInstance);
    }
    
    public function testInvokeClassWithDependance()
    {
        $container = new Container();
        $container->set(FooInterface::class, Foo::class);
        $container->set(BarInterface::class, Bar::class);
        $barInstance = $container->get(BarInterface::class);
        $this->assertInstanceOf(Bar::class, $barInstance);
    }
}