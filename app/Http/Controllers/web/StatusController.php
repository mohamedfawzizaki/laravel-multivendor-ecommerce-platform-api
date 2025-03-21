<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use Database\Factories\StatusFactory;

class StatusController extends Controller
{
    public function create()
    {
        $statuses = Status::factory()
                        ->state(StatusFactory::allowedSequence())
                        // ->withUsers(3)
                        ->count(StatusFactory::nOfStatusesToBeCreated())
                        ->create();  
        return $statuses;
    }
    public function show()
    {
        
    }
    public function update()
    {
        
    }
    public function delete()
    {
        
    }
}