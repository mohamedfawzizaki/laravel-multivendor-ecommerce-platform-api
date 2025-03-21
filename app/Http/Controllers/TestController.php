<?php

namespace App\Http\Controllers;

class TestController extends Controller
{
    use TestTrait;

    public $var = 23;
    public function test()
    {
        return $this->testMethod();
    }
    public function fromController(){
        return 'from controller';
    }
}