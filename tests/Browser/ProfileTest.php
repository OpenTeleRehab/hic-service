<?php

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\Group;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @returns void
 */
#[Group('ProfileTest')]
class ProfileTest extends DuskTestCase
{

    /**
     * @return void
     */
    #[Group('UpdateProfileTest')]
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
     * @return void
     */
    #[Group('UpdateProfileByCountryAdminTest')]
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
