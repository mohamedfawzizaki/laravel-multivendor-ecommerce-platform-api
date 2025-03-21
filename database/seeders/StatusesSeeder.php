<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;
use Database\Factories\StatusFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StatusesSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = Status::factory()
                    ->count(StatusFactory::nOfStatusesToBeCreated())
                    ->state(StatusFactory::allowedSequence())
                    ->make();
        // Iterate over the collection and save each role
        $statuses->each(function ($status) {
            $statusInDB = Status::where('name', $status->name)->first();
            if (!$statusInDB) {
                $status->save();
            }
        });
    }
}