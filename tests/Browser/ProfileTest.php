<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @group ProfileTest
 * @package Tests\Browser
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
                ->waitForText('Dashboard')
                ->visit('/profile')
                ->waitForText('Personal')
                ->clickLink('Edit')
                ->type('last_name', 'User')
                ->type('first_name', 'Testing')
                ->select('gender', 'female')
                ->press('Save')
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
                ->waitForText('Dashboard')
                ->visit('/profile')
                ->waitForText('Personal')
                ->clickLink('Edit')
                ->type('last_name', 'User')
                ->type('first_name', 'Testing')
                ->select('gender', 'female')
                ->press('Save')
                ->waitForText('User Testing');
            $this->logout($browser);
        });
    }

}
