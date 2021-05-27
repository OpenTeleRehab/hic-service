<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @group ProfileTest
 *
 * @returns void
 */
class ProfileTest extends DuskTestCase
{

    /**
     * @group UpdateProfileTest
     *
     * @return void
     */
    public function testUpdateProfileByGlobalAdmin()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsGlobal($browser)
                ->visit('/profile')
                ->waitForText('Personal')
                ->pause(1000)
                ->type('last_name', 'User')
                ->type('first_name', 'Testing')
                ->select('gender', 'female')
                ->pause(1000)
                ->press('Save')
                ->pause(1000)
                ->waitForText('User Testing');
            $this->logout($browser);
        });
    }

    /**
     * @group UpdateProfileByCountryAdminTest
     *
     * @return void
     */
    public function testUpdateProfileByCountryAdmin()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsCountryAdmin($browser)
                ->visit('/profile')
                ->waitForText('Personal')
                ->pause(1000)
                ->type('last_name', 'User')
                ->type('first_name', 'Testing')
                ->select('gender', 'female')
                ->pause(1000)
                ->press('Save')
                ->pause(1000)
                ->waitForText('User Testing');
            $this->logout($browser);
        });
    }
}
