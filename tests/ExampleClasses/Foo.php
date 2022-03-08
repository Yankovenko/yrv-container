<?php

namespace Tests\ExampleClasses;

class Foo implements FooInterface
{
    static public function staticMethod() {
        return 'ResultStaticMethod';
    }

    public  function notStaticMethod() {
        return 'ResultNotStaticMethod';
    }

}