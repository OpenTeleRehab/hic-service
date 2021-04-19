<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @group LanguageTest
 *
 * @returns void
 */
class LanguageTest extends DuskTestCase
{

    /**
     * @group ListLanguageTest
     *
     * @return void
     */
    public function testListLanguage()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting#language')
                ->pause(1000)
                ->assertPathIs('/setting')
                ->waitForText('English');
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
                ->pause(1000)
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
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/setting#language')
                ->waitForText('English')
                ->pause(1000)
                ->press('svg[viewBox="0 0 24 24"]')
                ->type('name', 'China')
                ->type('code', 'CN')
                ->press('Save')
                ->waitForText('Language updated successfully')
                ->waitForText('China');
            $this->logout($browser);
        });
    }
}
