<?php

namespace App\Http\Controllers;


trait TestTrait
{
    public function testMethod()
    {
        return 'Test method called from trait' . $this->var;
    }
}