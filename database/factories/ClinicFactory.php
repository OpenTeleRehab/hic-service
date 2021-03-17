<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Clinic::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Clinic A',
            'country_id' => 1,
            'region' => 'Cambodia',
            'province' => 'Siem Reap',
            'city' => 'Siem Reap'
        ];
    }
}
