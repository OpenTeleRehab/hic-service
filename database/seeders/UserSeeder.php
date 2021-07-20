<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            'email' => 'global-admin@we.co',
            'password' => bcrypt('global-admin@we.co'),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Country',
            'last_name' => 'Admin',
            'type' => 'country_admin',
            'email' => 'country-admin@we.co',
            'password' => bcrypt('country-admin@we.co'),
            'country_id' => 1,
        ]);

        DB::table('users')->insert([
            'first_name' => 'Clinic',
            'last_name' => 'Admin',
            'type' => 'clinic_admin',
            'email' => 'clinic-admin@we.co',
            'password' => bcrypt('clinic-admin@we.co'),
            'country_id' => 1,
            'clinic_id' => 1,
        ]);
    }
}
