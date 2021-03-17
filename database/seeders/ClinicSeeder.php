<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClinicSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('clinics')->insert([
            'name' => 'Clinic A',
            'country_id' => 1,
            'region' => 'Cambodia',
            'province' => 'Phnom Penh',
            'city' => 'Phnom Penh',
            'is_used' => 0
        ]);
    }
}
