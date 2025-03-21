<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Database\Factories\RoleFactory;
use Illuminate\Database\Query\Builder;
use Symfony\Component\VarDumper\VarDumper;
use Illuminate\Database\Eloquent\Collection;

class UserController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function create()
    {
        // return DB::table('users')->where('username', 'dare.osborne')->first();
        // return DB::select("SELECT * FROM users WHERE username = 'nona.swaniawski'");
        // $user = DB::table('users')->where('username', 'John')->firstOrFail();
        // return $user->username;
        // $emails = DB::table('users')->pluck('email');
        // foreach ($emails as $email) {
        //     echo $email . '</br>';
        // }
        // return $user = DB::table('users')->where('username', 'nona.swaniawski')->value('email');
        // $users = DB::table('users')->where('username', 'nona.swaniawski')->toSql();
        // dd($users); 
        // DB::table('users')->where('role', 'admin')->dd();
        // $users = DB::select("SELECT * FROM users WHERE username = ?", ['nona.swaniawski']);
        // dd($users);
        // $users = DB::table('users')->where('username', 'like', '%n%')->get();
        // dd($users);
        $users = DB::table('users')
        ->whereNot(function (Builder $query) {
            $query->where('role_id', 1)
            ->orWhere('status_id', 1);
        })->dd();
        dd($users);

    }
    public function show()
    {
        // // Initialize an empty array to store products
        // $allUsers = [];

        // // Fetch products in chunks and merge them into the array
        // DB::table('users')->orderBy('id')->chunk(5, function ($users) use (&$allUsers) {
        //     foreach ($users as $user) {
        //         $allUsers[] = $user;
        //     }
        // });

        // // Pass the collected products to the view
        // return var_dump($allUsers);
        $userRepo = new UserRepository(new User());
        $users = $this->userRepository->getAll();
        dd($users);




        
    }
    public function update()
    {
        $data = DB::table('users')->join('roles', 'users.role_id', 'roles.id')->get();
        dd($data);
    }
    public function delete()
    {
        
    }
}