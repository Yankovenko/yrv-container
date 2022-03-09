<?php


namespace Tests;


use Psr\Container\ContainerInterface;
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
        $container->set('test', function() {return 'asdf';});
        $this->assertEquals('asdf', $container->get('test'));
    }

    public function testSingleton()
    {
        $container = new Container();
        $container->set('test', function() {
            static $i=0;
            return $i++;
        });
        $this->assertEquals(0, $container->get('test'));
        $this->assertEquals(0, $container->get('test'));
    }

    public function testFactory()
    {
        $container = new Container();
        $container->factory('test', function(ContainerInterface $container) {
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
        $this->assertEquals('fooYRV\Container\Container', $container->get('test'));
    }

    public function testCall()
    {
        $container = new Container();
        $container->file('test', __DIR__ . '/files/file2.php');
        $this->assertEquals('', $container->call('test'));
        $this->assertEquals('a1', $container->call('test', 'a1'));
        $this->assertEquals('a1a2', $container->call('test', 'a1' , 'a2'));
    }

    public function testUnset()
    {
        $container = new Container();

        $function = function() {return 123;};
        $container->set('singleton', $function);
        $container->factory('factory', $function);
        $container->file('file', 'any/path');

        $this->assertTrue($container->has('singleton'));
        $container->unset('singleton');
        $this->assertFalse($container->has('singleton'));

        $this->assertTrue($container->has('factory'));
        $container->unset('factory');
        $this->assertFalse($container->has('factory'));

        $this->assertTrue($container->has('file'));
        $container->unset('file');
        $this->assertFalse($container->has('file'));
    }


}