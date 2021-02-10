<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CountryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Country::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Cambodia',
            'iso_code' => 'KH',
            'phone_code' => '855',
            'language_id' => Language::factory()->create()
        ];
    }
}

