<?php


namespace Tests;


use YRV\Container\Container;

class TestTest extends \PHPUnit\Framework\TestCase

{
    public function testOne()
    {
        $this->assertTrue(is_callable([AB::class, 'b']));

    }

}

class A {
    static function a() {}
};
class AB {
    static function b() {}
}