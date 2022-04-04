<?php

namespace Tests;

use Tests\ExampleClasses\Foo;
use YRV\Container\Container;
use YRV\Container\ContainerException;

class ParamsResolveTest extends \PHPUnit\Framework\TestCase

{
    public function testResolveEmptyParamWithError()
    {

        $container = new Container();
        $function = function (string $name) {
            return $name;
        };
        $this->expectException(ContainerException::class);
        $container->resolve($function);
    }

    public function testResolveEmptyParamWithoutError()
    {
        $container = new Container();
        $function = function (string $name = null) {
            return $name ?? 'null';
        };
        $result = $container->resolve($function);
        $this->assertEquals('null', $result);
    }

    public function test1ResolveParams()
    {
        $container = new Container();
        $container->set('string', 'stringValue');
        $container->set('name', 'nameValue');
        $function = function (string $param1, string $param2 = '') {
            return $param1 . $param2;
        };
        $result = $container->resolve($function);
        $this->assertEquals('stringValue', $result);
    }

    public function test2ResolveParams()
    {
        $container = new Container();
        $container->set('string', 'stringValue');
        $container->set('name', 'nameValue');
        $function = function (string $param1, string $param2 = '') {
            return $param1 . $param2;
        };
        $result = $container->resolve($function, ['param1' => '1', 'param2' => '2']);
        $this->assertEquals('12', $result);
    }

    public function test3ResolveParams()
    {
        $container = new Container();
        $container->set('string', 'StringValue');
        $container->set('param2', 'NameValue');
        $function = function (string $param1, string $param2, string $param3 = '') {
            return $param1 . $param2 . $param3;
        };
        $result = $container->resolve($function, ['param3' => '3']);
        $this->assertEquals('StringValueStringValue3', $result);
    }

    public function test4ResolveParams()
    {
        $container = new Container();
        $container->set('string', 'StringValue');
        $container->set('param2', 'NameValue');
        $function = function (string $param1, $param2, string $param3 = '') {
            return $param1 . $param2 . $param3;
        };
        $result = $container->resolve($function, ['param3' => '3']);
        $this->assertEquals('StringValueNameValue3', $result);
    }

}