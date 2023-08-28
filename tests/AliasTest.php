<?php

namespace Tests;

use Tests\ExampleClasses\Foo;
use YRV\Container\Container;
use YRV\Container\RecursiveContainerException;

class AliasTest extends \PHPUnit\Framework\TestCase
{
    public function testAlias1()
    {
        $container = new Container();

        $container->set('foo', [Foo::class, 'staticMethod']);
        $container->alias('alias1', 'foo');

        $this->assertEquals('ResultStaticMethod', $container->get('alias1'));
    }

    public function testAlias2()
    {
        $container = new Container();

        $container->set('foo', [Foo::class, 'staticMethod']);
        $container->alias('alias1', 'foo');
        $container->alias('alias2', 'alias1');

        $this->assertEquals('ResultStaticMethod', $container->get('alias2'));
    }

    public function testAlias3()
    {
        $container = new Container();

        $container->set('foo', [Foo::class, 'staticMethod']);
        $container->alias('alias1', 'foo');
        $container->alias('alias2', 'alias1');
        $container->alias('alias3', 'alias2');

        $this->assertEquals('ResultStaticMethod', $container->get('alias3'));
    }


    public function testAliasRecursiveFail()
    {
        $container = new Container();

        $container->set('foo', [Foo::class, 'staticMethod']);
        $container->alias('alias1', 'alias2');
        $container->alias('alias2', 'alias1');

        $this->expectException(RecursiveContainerException::class);
        $container->get('alias1');

    }
}