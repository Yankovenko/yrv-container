<?php


namespace Tests;


use Tests\ExampleClasses\Bar;
use Tests\ExampleClasses\BarInterface;
use Tests\ExampleClasses\Foo;
use Tests\ExampleClasses\FooInterface;
use YRV\Container\Container;

class FunctionsTest extends \PHPUnit\Framework\TestCase

{
    public function testInvokeFunction()
    {
        $container = new Container();
        $fn = function() {
            return 'function123';
        };
        $container->set('test', $fn);
        $this->assertEquals('function123', $container->get('test'));
    }

    public function testInvokeFunctionWithParamaters()
    {
        $container = new Container();
        $fn = function(string $arg1, object $arg2, $arg3='value3') {
            return $arg1 . get_class($arg2) . $arg3;
        };
        $container->set('test', $fn);
        $result = $container->get('test', ['arg1' => 'value1', 'arg2'=>new \stdClass()]);
        $this->assertEquals('value1stdClassvalue3', $result);
    }

    public function testNotInvokeFunctionByName()
    {
        $container = new Container();
        $container->set('test', 'functionTest');
        $this->assertEquals('functionTest', $container->get('test'));

    }

}

function functionTest() {
    return 'ResultFunctionTest';
}