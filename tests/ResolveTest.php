<?php

namespace Tests;

use Tests\ExampleClasses\Foo;
use YRV\Container\Container;

class ResolveTest extends \PHPUnit\Framework\TestCase

{

    public function testResolveObject()
    {
        $container = new Container();
        $fooInstance = $container->resolve(Foo::class);
        $this->assertInstanceOf(Foo::class, $fooInstance);
    }

    public function testResolveStaticMethod()
    {
        $container = new Container();
        $result = $container->resolve([Foo::class, 'staticMethod']);
        $this->assertEquals('ResultStaticMethod', $result);
    }

    public function testResolveNonStaticMethod()
    {
        $container = new Container();
        $result = $container->resolve([Foo::class, 'notStaticMethod']);
        $this->assertEquals('ResultNotStaticMethod', $result);
    }

    public function testResolveWithUseGet()
    {
        $container = new Container();
        $container->set(Foo::class, function() {
            return 'FOO';
        });
        $result = $container->resolve(Foo::class);
        $this->assertEquals('FOO', $result);
    }
}