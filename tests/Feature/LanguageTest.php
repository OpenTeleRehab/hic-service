<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Language;

class LanguageTest extends TestCase
{
    /**
     * @group FeatureListLanguageTest
     *
     * @return void
     */
    public function testListLanguage()
    {
        $globalAdmin = $this->getGlobalAdmin();
        $response = $this->actingAs($globalAdmin)->get('/api/language');
        $response->assertStatus(200);
    }

    /**
     * @group FeatureCreateLanguageTest
     *
     * @return void
     */
    public function testCreateLanguage()
    {
        $globalAdmin = $this->getGlobalAdmin();
        $response = $this->actingAs($globalAdmin)->post('/api/language',[
            'name' => 'Cambodai',
            'code' => 'kh'
        ]);
        $response->assertJson(['success' => true,"message" => "success_message.language_add"]);
        $response->assertStatus(200);
        $this->assertDatabaseCount('languages', 1);
    }

    /**
     * @group FeatureEditLanguageTest
     *
     * @return void
     */
    public function testEditLanguage()
    {
        $language = Language::factory()->create();
        $globalAdmin = $this->getGlobalAdmin();
        $response = $this->actingAs($globalAdmin)
            ->put('/api/language/'.$language->id, [
                'name' => 'Thailand',
                'code' => $language->code
            ]);

        $response->assertJson(['success' => true,"message" => "success_message.language_update"]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('languages', [
            'name' => 'Thailand'
        ]);
    }
}
