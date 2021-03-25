<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Language;
use App\Models\Country;

class CountryTest extends TestCase
{
    /**
     * @group FeatureListCountryTest
     *
     * @return void
     */
    public function testListCountry()
    {
        $globalAdmin = $this->getGlobalAdmin();
        $response = $this->actingAs($globalAdmin)->get('/api/country');
        $response->assertStatus(200);
    }

    /**
     * @group FeatureCreateCountryTest
     *
     * @return void
     */
    public function testCreateCountry()
    {
        $globalAdmin = $this->getGlobalAdmin();
        $language = Language::Factory()->create();
        $response = $this->actingAs($globalAdmin)->post('/api/country', [
            'name' => 'Japan',
            'iso_code' => 'jp',
            'phone_code' => '81',
            'language' => $language->id
        ]);
        $response->assertJson(['success' => true, 'message' => 'success_message.country_add']);
        $this->assertDatabaseCount('countries', 2);
        $this->assertDatabaseHas('countries', [
            'name' => 'Japan'
        ]);
    }

    /**
     * @group FeatureEditCountryTest
     *
     * @return void
     */
    public function testEditCountry()
    {
        $country = Country::factory()->create();
        $globalAdmin = $this->getGlobalAdmin();
        $response = $this->actingAs($globalAdmin)->put('/api/country/1', [
            'name' => 'Vietnam',
            'iso_code' => 'vn',
            'phone_code' => '84',
            'language' => $country->language_id
        ]);
        $response->assertJson(['success' => true, 'message' => 'success_message.country_update']);
        $this->assertDatabaseHas('countries', [
            'name' => 'Vietnam'
        ]);
    }
}
