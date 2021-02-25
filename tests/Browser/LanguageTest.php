<?php

namespace Tests\Browser;

use App\Models\Country;
use App\Models\Language;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LanguageTest extends DuskTestCase
{

    /**
     * @group ListLanguageTest
     *
     * @return void
     */
    public function testListLanguage()
    {
        $language = Language::factory()->create();
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting#language')
                ->assertPathIs('/setting')
                ->waitForText('USA');
            $this->logout($browser);
        });
    }

    /**
     * @group CreateLanguageTest
     *
     * @return void
     */
    public function testCreateLanguage()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting#language')
                ->waitForText('New Language')
                ->press('New Language')
                ->type('name', 'Khmer')
                ->type('code', 'kh')
                ->press('Create')
                ->waitForText('Language created successfully')
                ->waitForText('Khmer');
            $this->logout($browser);
        });
    }

    /**
     * @group EditLanguageTest
     *
     * @return void
     */
    public function testEditLanguage()
    {
        $language = Language::factory()->create();
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting#language')
                ->waitForText('USA')
                ->press('svg[viewBox="0 0 24 24"]')
                ->type('name', 'China')
                ->type('code', 'CN')
                ->press('Save')
                ->waitForText('Language updated successfully')
                ->assertSee('China');
            $this->logout($browser);
        });
    }
}
