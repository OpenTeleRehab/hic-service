<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use App\Models\Language;

class LanguageTest extends TestCase
{
    /**
     * @return void
     */
    #[Group('FeatureListLanguageTest')]
    public function testListLanguage()
    {
        $globalAdmin = $this->getGlobalAdmin();
        $response = $this->actingAs($globalAdmin)->get('/api/language');
        $response->assertStatus(200);
    }

    /**
     * @return void
     */
    #[Group('FeatureCreateLanguageTest')]
    public function testCreateLanguage()
    {
        $globalAdmin = $this->getGlobalAdmin();
        $response = $this->actingAs($globalAdmin)->post('/api/language',[
            'name' => 'Cambodia',
            'code' => 'kh'
        ]);
        $response->assertJson(['success' => true,"message" => "success_message.language_add"]);
        $response->assertStatus(200);
        $this->assertDatabaseCount('languages', 2);
    }

    /**
     * @return void
     */
    #[Group('FeatureEditLanguageTest')]
    public function testEditLanguage()
    {
        $language = Language::factory()->create();
        $globalAdmin = $this->getGlobalAdmin();
        $response = $this->actingAs($globalAdmin)
            ->put('/api/language/'.$language->id, [
                'name' => 'Thailand',
                'code' => 66
            ]);

        $response->assertJson(['success' => true,"message" => "success_message.language_update"]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('languages', [
            'name' => 'Thailand'
        ]);
    }
}
