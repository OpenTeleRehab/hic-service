<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'first_name' => 'Global',
            'last_name' => 'Admin',
            'type' => 'global_admin',
            'email' => 'adminuser@gmail.com',
            'password' => bcrypt('adminuser@gmail.com'),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Country',
            'last_name' => 'Admin',
            'type' => 'country_admin',
            'email' => 'country@gmail.com',
            'password' => bcrypt('country@gmail.com'),
            'country_id' => 1,
        ]);
    }
}
