<?php


namespace Tests;


use YRV\Container\Container;

class BaseTest extends \PHPUnit\Framework\TestCase

{
    public function testScalar()
    {
        $container = new Container();
        $container->set('test', 'test123');
        $this->assertEquals('test123', $container->get('test'));
    }

    public function testFunction()
    {
        $container = new Container();
        $container->set('test', function($container) {return 'asdf';});
        $this->assertEquals('asdf', $container->get('test'));
    }

    public function testSingleton()
    {
        $container = new Container();
        $container->set('test', function($container) {
            static $i=0;
            return $i++;
        });
        $this->assertEquals(0, $container->get('test'));
        $this->assertEquals(0, $container->get('test'));
    }

    public function testFactory()
    {
        $container = new Container();
        $container->factory('test', function($container) {
            static $i=0;
            return $i++;
        });
        $this->assertEquals(0, $container->get('test'));
        $this->assertEquals(1, $container->get('test'));
    }

    public function testFile()
    {
        $container = new Container();
        $container->file('test', __DIR__ . '/files/file1.php');
        $this->assertEquals('foo', $container->get('test'));
    }

    public function testCall()
    {
        $container = new Container();
        $container->file('test', __DIR__ . '/files/file2.php');
        $this->assertEquals('', $container->call('test'));
        $this->assertEquals('a1', $container->call('test', 'a1'));
        $this->assertEquals('a1a2', $container->call('test', 'a1' , 'a2'));
    }



}